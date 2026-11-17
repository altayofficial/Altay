<?php

/*
 *
 *      _    _ _
 *     / \  | | |_ __ _ _   _
 *    / _ \ | | __/ _` | | | |
 *   / ___ \| | || (_| | |_| |
 *  /_/   \_\_|\__\__,_|\__, |
 *                       |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Original work by the PocketMine Team.
 * https://www.pocketmine.net/
 *
 * @author Altay Team
 * @link https://github.com/altayofficial
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\block\utils\VoxelShape;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\network\mcpe\cache\EncodedPacketCache;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelCells;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelShape;
use pocketmine\network\mcpe\protocol\VoxelShapesPacket;
use pocketmine\utils\Utils;
use function array_fill;
use function array_is_list;
use function array_unique;
use function array_values;
use function count;
use function intdiv;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function preg_match;
use function sort;
use function str_starts_with;
use const JSON_THROW_ON_ERROR;
use const SORT_NUMERIC;
use const SORT_REGULAR;

/**
 * Keeps the named voxel shapes the client knows about. Custom blocks point at these by name, for example through
 * the culling_shape field of their minecraft:geometry component.
 *
 * Custom shapes have to be registered before the first player joins. The packet is built once on first use and
 * shared by every session, so registering after that throws.
 */
final class VoxelShapeDictionary{
	private const VANILLA_NAMESPACE = "minecraft:";

	//handles go over the wire as u16, and the cell counts as single bytes
	private const MAX_SHAPES = 0xffff;
	private const MAX_CELLS_PER_AXIS = 0xff;

	/**
	 * @var SerializableVoxelShape[]
	 * @phpstan-var array<string, SerializableVoxelShape>
	 */
	private array $customShapes = [];

	private ?VoxelShapesPacket $packet = null;

	/**
	 * @param SerializableVoxelShape[] $vanillaShapes
	 * @param int[]                    $vanillaNames
	 * @phpstan-param list<SerializableVoxelShape> $vanillaShapes
	 * @phpstan-param array<string, int>           $vanillaNames
	 */
	public function __construct(
		private readonly array $vanillaShapes,
		private readonly array $vanillaNames
	){}

	/**
	 * The client keeps its own copy of the vanilla shapes, but it only finds one by the name the server gives it,
	 * so the names have to travel with the shapes. A shape without a name is still sent, because the ones that
	 * have a name are found by their position in the list.
	 *
	 * @throws SavedDataLoadingException
	 */
	public static function loadFromString(string $json) : self{
		try{
			$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
		}catch(\JsonException $e){
			throw new SavedDataLoadingException("Invalid voxel shape JSON: " . $e->getMessage(), 0, $e);
		}
		if(!is_array($data) || !array_is_list($data)){
			throw new SavedDataLoadingException("Expected a list of vanilla voxel shapes");
		}

		$shapes = [];
		$names = [];
		foreach($data as $shape){
			if(!is_array($shape)){
				throw new SavedDataLoadingException("Invalid voxel shape entry");
			}

			$identifier = $shape["identifier"] ?? null;
			if(is_string($identifier)){
				$names[$identifier] = count($shapes);
			}

			$boxes = $shape["boxes"] ?? [];
			if(!is_array($boxes) || !array_is_list($boxes)){
				throw new SavedDataLoadingException("Invalid voxel shape boxes list");
			}
			$shapes[] = self::serializeVanillaBoxes($boxes);
		}

		return new self($shapes, $names);
	}

	/**
	 * Registers a custom shape under the given name. The name needs its own namespace, minecraft: is taken.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \LogicException if the shape packet was already sent out
	 */
	public function register(string $name, VoxelShape $shape) : void{
		if($this->packet !== null){
			throw new \LogicException("Voxel shapes must be registered before any player joins");
		}
		if(preg_match('/^[a-z0-9_.\-]+:[a-z0-9_.\-\/]+$/', $name) !== 1){
			throw new \InvalidArgumentException("Voxel shape name \"$name\" must look like namespace:name, lowercase");
		}
		if(str_starts_with($name, self::VANILLA_NAMESPACE)){
			throw new \InvalidArgumentException("Voxel shape name \"$name\" uses the reserved minecraft namespace");
		}
		if(isset($this->customShapes[$name])){
			throw new \InvalidArgumentException("Voxel shape \"$name\" is already registered");
		}
		if(count($this->vanillaShapes) + count($this->customShapes) >= self::MAX_SHAPES){
			throw new \InvalidArgumentException("Too many voxel shapes, the protocol only allows " . self::MAX_SHAPES);
		}

		$this->customShapes[$name] = self::serializeShape($shape);
	}

	public function isRegistered(string $name) : bool{
		return isset($this->vanillaNames[$name]) || isset($this->customShapes[$name]);
	}

	/**
	 * Returns the packet with every vanilla shape followed by the custom ones. Calling this locks the dictionary.
	 */
	public function getPacket() : VoxelShapesPacket{
		if($this->packet === null){
			$shapes = $this->vanillaShapes;
			$names = $this->vanillaNames;
			foreach(Utils::stringifyKeys($this->customShapes) as $name => $shape){
				$names[$name] = count($shapes);
				$shapes[] = $shape;
			}
			$this->packet = VoxelShapesPacket::create($shapes, $names, count($this->customShapes));
			EncodedPacketCache::getInstance()->markImmutable($this->packet);
		}
		return $this->packet;
	}

	/**
	 * Packs a shape the way the client expects: one bit per cell, x outermost, LSB first.
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function serializeShape(VoxelShape $shape) : SerializableVoxelShape{
		if($shape->isEmpty()){
			return self::emptyShape();
		}

		$xPlanes = $shape->getXPlanes();
		$yPlanes = $shape->getYPlanes();
		$zPlanes = $shape->getZPlanes();
		$nx = count($xPlanes) - 1;
		$ny = count($yPlanes) - 1;
		$nz = count($zPlanes) - 1;
		if($nx > self::MAX_CELLS_PER_AXIS || $ny > self::MAX_CELLS_PER_AXIS || $nz > self::MAX_CELLS_PER_AXIS){
			throw new \InvalidArgumentException("Voxel shape is too detailed, at most " . self::MAX_CELLS_PER_AXIS . " cells per axis can be sent");
		}

		$storage = array_fill(0, intdiv($nx * $ny * $nz + 7, 8), 0);
		$index = 0;
		for($x = 0; $x < $nx; ++$x){
			for($y = 0; $y < $ny; ++$y){
				for($z = 0; $z < $nz; ++$z){
					if($shape->isCellFilled($x, $y, $z)){
						$storage[$index >> 3] |= 1 << ($index & 7);
					}
					++$index;
				}
			}
		}

		return new SerializableVoxelShape(new SerializableVoxelCells($nx, $ny, $nz, array_values($storage)), $xPlanes, $yPlanes, $zPlanes);
	}

	private static function emptyShape() : SerializableVoxelShape{
		//an empty shape still carries one cutting plane per axis, the same as the game sends
		return new SerializableVoxelShape(new SerializableVoxelCells(0, 0, 0, []), [0.0], [0.0], [0.0]);
	}

	/**
	 * Vanilla shapes are packed straight from their pixel boxes without merging any planes, so they come out
	 * exactly like the ones the game sends itself.
	 *
	 * @param mixed[] $boxes
	 * @phpstan-param list<mixed> $boxes
	 *
	 * @throws SavedDataLoadingException
	 */
	private static function serializeVanillaBoxes(array $boxes) : SerializableVoxelShape{
		if(count($boxes) === 0){
			return self::emptyShape();
		}

		$parsed = [];
		$xPlanes = $yPlanes = $zPlanes = [];
		foreach($boxes as $box){
			if(
				!is_array($box) || count($box) !== 2 || !isset($box[0], $box[1]) ||
				!is_array($box[0]) || !is_array($box[1]) || count($box[0]) !== 3 || count($box[1]) !== 3
			){
				throw new SavedDataLoadingException("Invalid voxel box");
			}
			[$min, $max] = $box;
			if(
				!isset($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]) ||
				!is_int($min[0]) || !is_int($min[1]) || !is_int($min[2]) ||
				!is_int($max[0]) || !is_int($max[1]) || !is_int($max[2])
			){
				throw new SavedDataLoadingException("Invalid voxel box coordinate");
			}
			$parsed[] = [$min[0] / 16.0, $min[1] / 16.0, $min[2] / 16.0, $max[0] / 16.0, $max[1] / 16.0, $max[2] / 16.0];
			$xPlanes[] = $min[0] / 16.0;
			$xPlanes[] = $max[0] / 16.0;
			$yPlanes[] = $min[1] / 16.0;
			$yPlanes[] = $max[1] / 16.0;
			$zPlanes[] = $min[2] / 16.0;
			$zPlanes[] = $max[2] / 16.0;
		}

		$xPlanes = array_values(array_unique($xPlanes, SORT_REGULAR));
		$yPlanes = array_values(array_unique($yPlanes, SORT_REGULAR));
		$zPlanes = array_values(array_unique($zPlanes, SORT_REGULAR));
		sort($xPlanes, SORT_NUMERIC);
		sort($yPlanes, SORT_NUMERIC);
		sort($zPlanes, SORT_NUMERIC);

		$nx = count($xPlanes) - 1;
		$ny = count($yPlanes) - 1;
		$nz = count($zPlanes) - 1;
		$storage = array_fill(0, intdiv($nx * $ny * $nz + 7, 8), 0);
		$index = 0;
		for($x = 0; $x < $nx; ++$x){
			$midX = ($xPlanes[$x] + $xPlanes[$x + 1]) / 2;
			for($y = 0; $y < $ny; ++$y){
				$midY = ($yPlanes[$y] + $yPlanes[$y + 1]) / 2;
				for($z = 0; $z < $nz; ++$z){
					$midZ = ($zPlanes[$z] + $zPlanes[$z + 1]) / 2;
					foreach($parsed as [$minX, $minY, $minZ, $maxX, $maxY, $maxZ]){
						if($midX >= $minX && $midX <= $maxX && $midY >= $minY && $midY <= $maxY && $midZ >= $minZ && $midZ <= $maxZ){
							$storage[$index >> 3] |= 1 << ($index & 7);
							break;
						}
					}
					++$index;
				}
			}
		}

		return new SerializableVoxelShape(new SerializableVoxelCells($nx, $ny, $nz, array_values($storage)), $xPlanes, $yPlanes, $zPlanes);
	}
}
