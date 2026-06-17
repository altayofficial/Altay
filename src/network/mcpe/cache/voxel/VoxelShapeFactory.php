<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\cache\voxel;

use pocketmine\network\mcpe\cache\voxel\shape\VoxelBox;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelCells;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelShape;
use pocketmine\network\mcpe\protocol\VoxelShapesPacket;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use function array_keys;
use function array_unique;
use function count;
use function intdiv;
use function max;
use function sort;
use const SORT_REGULAR;

/**
 * Holds the voxel shapes sent to clients in the {@link VoxelShapesPacket} during the pre-spawn phase.
 *
 * The two builtin shapes ({@code minecraft:empty} and {@code minecraft:unit_cube}) are always registered first, so
 * custom shapes registered afterwards line up with the client-side expectation that the first two entries are vanilla.
 */
final class VoxelShapeFactory{
	use SingletonTrait;

	private const SHAPE_EMPTY = "minecraft:empty";
	private const SHAPE_UNIT_CUBE = "minecraft:unit_cube";

	/**
	 * @var SerializableVoxelShape[]
	 * @phpstan-var array<string, SerializableVoxelShape>
	 */
	private array $shapes = [];

	private VoxelShapesPacket $packet;

	public function __construct(){
		$this->shapes[self::SHAPE_EMPTY] = new SerializableVoxelShape(
			new SerializableVoxelCells(0, 0, 0, []),
			[0.0], [0.0], [0.0]
		);
		$this->shapes[self::SHAPE_UNIT_CUBE] = new SerializableVoxelShape(
			new SerializableVoxelCells(1, 1, 1, [1]),
			[0.0, 1.0], [0.0, 1.0], [0.0, 1.0]
		);

		$this->rebuildPacket();
	}

	/**
	 * Registers a shape built from a list of axis-aligned boxes (coordinates in block-sixteenths, 0-16).
	 *
	 * @param VoxelBox[] $boxes
	 * @phpstan-param list<VoxelBox> $boxes
	 */
	public function register(string $key, array $boxes) : void{
		$this->registerShape($key, $this->convertBoxesToShape($boxes));
	}

	public function registerShape(string $key, SerializableVoxelShape $shape) : void{
		if(isset($this->shapes[$key])){
			throw new \InvalidArgumentException("The voxel shape $key has already been registered");
		}
		$this->shapes[$key] = $shape;
		$this->rebuildPacket();
	}

	public function get(string $key) : ?SerializableVoxelShape{
		return $this->shapes[$key] ?? null;
	}

	public function getPacket() : VoxelShapesPacket{
		return $this->packet;
	}

	private function rebuildPacket() : void{
		$nameMap = [];
		$shapes = [];
		$i = 0;
		foreach(Utils::stringifyKeys($this->shapes) as $name => $shape){
			$nameMap[$name] = $i++;
			$shapes[] = $shape;
		}

		$this->packet = VoxelShapesPacket::create($shapes, $nameMap, count($this->shapes) - 2); //the two builtin shapes (empty + unit_cube) are not counted as custom shapes
	}

	/**
	 * @param VoxelBox[] $boxes
	 * @phpstan-param list<VoxelBox> $boxes
	 */
	private function convertBoxesToShape(array $boxes) : SerializableVoxelShape{
		if(count($boxes) === 0){
			return new SerializableVoxelShape(new SerializableVoxelCells(0, 0, 0, []), [0.0], [0.0], [0.0]);
		}

		$xCoords = $this->getAxisBoundaries($boxes, VoxelBox::AXIS_X);
		$yCoords = $this->getAxisBoundaries($boxes, VoxelBox::AXIS_Y);
		$zCoords = $this->getAxisBoundaries($boxes, VoxelBox::AXIS_Z);

		$resX = count($xCoords) - 1;
		$resY = count($yCoords) - 1;
		$resZ = count($zCoords) - 1;

		$bits = [];
		for($z = 0; $z < $resZ; $z++){
			for($y = 0; $y < $resY; $y++){
				for($x = 0; $x < $resX; $x++){
					$midX = ($xCoords[$x] + $xCoords[$x + 1]) / 2.0;
					$midY = ($yCoords[$y] + $yCoords[$y + 1]) / 2.0;
					$midZ = ($zCoords[$z] + $zCoords[$z + 1]) / 2.0;

					if($this->isInside($midX, $midY, $midZ, $boxes)){
						$bits[$x + ($y * $resX) + ($z * $resX * $resY)] = true;
					}
				}
			}
		}

		$storage = [];
		foreach($bits as $index => $_){
			$byteIndex = intdiv($index, 8);
			$storage[$byteIndex] = ($storage[$byteIndex] ?? 0) | (1 << ($index % 8));
		}

		$bitmask = [];
		if(count($bits) !== 0){
			$maxByte = intdiv(max(array_keys($bits)), 8);
			for($b = 0; $b <= $maxByte; $b++){
				$bitmask[] = $storage[$b] ?? 0;
			}
		}

		return new SerializableVoxelShape(new SerializableVoxelCells($resX, $resY, $resZ, $bitmask), $xCoords, $yCoords, $zCoords);
	}

	/**
	 * @param VoxelBox[] $boxes
	 * @phpstan-param list<VoxelBox> $boxes
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	private function getAxisBoundaries(array $boxes, int $axis) : array{
		$bounds = [0.0, 1.0];
		foreach($boxes as $box){
			$bounds[] = $box->getMin($axis) / 16.0;
			$bounds[] = $box->getMax($axis) / 16.0;
		}
		$bounds = array_unique($bounds, SORT_REGULAR);
		sort($bounds);
		return $bounds;
	}

	/**
	 * @param VoxelBox[] $boxes
	 * @phpstan-param list<VoxelBox> $boxes
	 */
	private function isInside(float $x, float $y, float $z, array $boxes) : bool{
		foreach($boxes as $box){
			if(
				$x >= $box->getMin(VoxelBox::AXIS_X) / 16.0 && $x <= $box->getMax(VoxelBox::AXIS_X) / 16.0 &&
				$y >= $box->getMin(VoxelBox::AXIS_Y) / 16.0 && $y <= $box->getMax(VoxelBox::AXIS_Y) / 16.0 &&
				$z >= $box->getMin(VoxelBox::AXIS_Z) / 16.0 && $z <= $box->getMax(VoxelBox::AXIS_Z) / 16.0
			){
				return true;
			}
		}
		return false;
	}
}
