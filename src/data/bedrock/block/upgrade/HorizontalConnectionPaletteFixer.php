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

namespace pocketmine\data\bedrock\block\upgrade;

use pocketmine\block\Block;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\Stair;
use pocketmine\block\Thin;
use pocketmine\block\Tripwire;
use pocketmine\block\TripwireHook;
use pocketmine\block\utils\StairShape;
use pocketmine\block\utils\SupportType;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\math\Facing;
use pocketmine\world\format\SubChunk;

final class HorizontalConnectionPaletteFixer{

	private function __construct(){
	}

	/**
	 * Works out the connections and stair corners of a chunk whose blocks predate them.
	 *
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks the chunk at
	 *        an offset of x and z chunks from this one, for the blocks on the edges. Without it they
	 *        are worked out as if the chunk stood on its own, which leaves a fence looking cut off at
	 *        every chunk border until someone builds next to it.
	 *
	 * @return bool whether anything changed
	 */
	public static function fix(array $subChunks, ?\Closure $neighbourChunks = null) : bool{
		$registry = RuntimeBlockStateRegistry::getInstance();
		if(!self::containsConnectable($subChunks, $registry)){
			return false;
		}

		$changed = false;
		foreach($subChunks as $subY => $subChunk){
			if(self::fixSubChunk($subChunks, $subY, $subChunk, $registry, $neighbourChunks)){
				$changed = true;
			}
		}
		return $changed;
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 */
	private static function containsConnectable(array $subChunks, RuntimeBlockStateRegistry $registry) : bool{
		foreach($subChunks as $subChunk){
			if($subChunk->isEmptyFast()){
				continue;
			}
			foreach($subChunk->getBlockLayers() as $layer){
				foreach($layer->getPalette() as $stateId){
					$probe = $registry->fromStateId($stateId);
					if($probe instanceof Stair || $probe instanceof Fence || $probe instanceof Thin || $probe instanceof Tripwire){
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function fixSubChunk(array $subChunks, int $subY, SubChunk $subChunk, RuntimeBlockStateRegistry $registry, ?\Closure $neighbourChunks) : bool{
		if($subChunk->isEmptyFast()){
			return false;
		}

		$scan = false;
		foreach($subChunk->getBlockLayers() as $layer){
			foreach($layer->getPalette() as $stateId){
				$probe = $registry->fromStateId($stateId);
				if($probe instanceof Stair || $probe instanceof Fence || $probe instanceof Thin || $probe instanceof Tripwire){
					$scan = true;
					break 2;
				}
			}
		}
		if(!$scan){
			return false;
		}

		$changed = false;
		$yBase = $subY << SubChunk::COORD_BIT_SIZE;
		for($x = 0; $x < SubChunk::EDGE_LENGTH; ++$x){
			for($z = 0; $z < SubChunk::EDGE_LENGTH; ++$z){
				for($y = 0; $y < SubChunk::EDGE_LENGTH; ++$y){
					$oldId = $subChunk->getBlockStateId($x, $y, $z);
					$block = $registry->fromStateId($oldId);
					if(!$block instanceof Stair && !$block instanceof Fence && !$block instanceof Thin && !$block instanceof Tripwire){
						continue;
					}

					$newId = self::recomputeStateId($block, $subChunks, $x, $yBase + $y, $z, $registry, $neighbourChunks);
					if($newId !== $oldId){
						$subChunk->setBlockStateId($x, $y, $z, $newId);
						$changed = true;
					}
				}
			}
		}
		return $changed;
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function recomputeStateId(
		Block $block,
		array $subChunks,
		int $x,
		int $y,
		int $z,
		RuntimeBlockStateRegistry $registry,
		?\Closure $neighbourChunks
	) : int{
		if($block instanceof Stair){
			$block->setShape(self::stairShape($block, $subChunks, $x, $y, $z, $registry, $neighbourChunks));
		}elseif($block instanceof Fence || $block instanceof Thin){
			foreach(Facing::HORIZONTAL as $facing){
				$side = self::neighbor($subChunks, $x, $y, $z, $facing, $registry, $neighbourChunks);
				$block->setConnected($facing, self::canConnect($block, $facing, $side));
			}
		}elseif($block instanceof Tripwire){
			foreach(Facing::HORIZONTAL as $facing){
				$side = self::neighbor($subChunks, $x, $y, $z, $facing, $registry, $neighbourChunks);
				$block->setConnection($facing, $side instanceof Tripwire || $side instanceof TripwireHook);
			}
		}

		return $block->getStateId();
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function neighbor(
		array $subChunks,
		int $x,
		int $y,
		int $z,
		Facing $facing,
		RuntimeBlockStateRegistry $registry,
		?\Closure $neighbourChunks
	) : Block{
		[$dx, $dy, $dz] = Facing::OFFSET[$facing->value];
		$nx = $x + $dx;
		$nz = $z + $dz;

		if($nx < 0 || $nx > SubChunk::COORD_MASK || $nz < 0 || $nz > SubChunk::COORD_MASK){
			if($neighbourChunks === null){
				return VanillaBlocks::AIR();
			}
			$subChunks = $neighbourChunks($nx >> SubChunk::COORD_BIT_SIZE, $nz >> SubChunk::COORD_BIT_SIZE);
			if($subChunks === null){
				return VanillaBlocks::AIR();
			}
			$nx &= SubChunk::COORD_MASK;
			$nz &= SubChunk::COORD_MASK;
		}

		$ny = $y + $dy;
		$sub = $subChunks[$ny >> SubChunk::COORD_BIT_SIZE] ?? null;
		if($sub === null){
			return VanillaBlocks::AIR();
		}

		return $registry->fromStateId($sub->getBlockStateId($nx, $ny & SubChunk::COORD_MASK, $nz));
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function stairShape(Stair $stair, array $subChunks, int $x, int $y, int $z, RuntimeBlockStateRegistry $registry, ?\Closure $neighbourChunks) : StairShape{
		$clockwise = Facing::rotateY($stair->getFacing()->toFacing(), true);
		$backFacing = self::possibleCornerFacing($stair, $subChunks, $x, $y, $z, $registry, false, $neighbourChunks);
		if(
			$backFacing !== null &&
			self::canTakeStairShape($stair, $subChunks, $x, $y, $z, $registry, Facing::opposite($backFacing), $neighbourChunks)
		){
			return $backFacing === $clockwise ? StairShape::OUTER_RIGHT : StairShape::OUTER_LEFT;
		}
		$frontFacing = self::possibleCornerFacing($stair, $subChunks, $x, $y, $z, $registry, true, $neighbourChunks);
		if(
			$frontFacing !== null &&
			self::canTakeStairShape($stair, $subChunks, $x, $y, $z, $registry, $frontFacing, $neighbourChunks)
		){
			return $frontFacing === $clockwise ? StairShape::INNER_RIGHT : StairShape::INNER_LEFT;
		}
		return StairShape::STRAIGHT;
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function possibleCornerFacing(Stair $stair, array $subChunks, int $x, int $y, int $z, RuntimeBlockStateRegistry $registry, bool $oppositeFacing, ?\Closure $neighbourChunks) : ?Facing{
		$side = self::neighbor(
			$subChunks,
			$x,
			$y,
			$z,
			$oppositeFacing ? Facing::opposite($stair->getFacing()->toFacing()) : $stair->getFacing()->toFacing(),
			$registry,
			$neighbourChunks
		);
		return (
			$side instanceof Stair &&
			$side->isUpsideDown() === $stair->isUpsideDown() &&
			Facing::axis($side->getFacing()->toFacing()) !== Facing::axis($stair->getFacing()->toFacing())
		) ? $side->getFacing()->toFacing() : null;
	}

	/**
	 * @param SubChunk[] $subChunks
	 * @phpstan-param array<int, SubChunk> $subChunks
	 * @phpstan-param (\Closure(int, int) : ?array<int, SubChunk>)|null $neighbourChunks
	 */
	private static function canTakeStairShape(Stair $stair, array $subChunks, int $x, int $y, int $z, RuntimeBlockStateRegistry $registry, Facing $facing, ?\Closure $neighbourChunks) : bool{
		$side = self::neighbor($subChunks, $x, $y, $z, $facing, $registry, $neighbourChunks);
		return !($side instanceof Stair) || $side->getFacing() !== $stair->getFacing() || $side->isUpsideDown() !== $stair->isUpsideDown();
	}

	private static function canConnect(Block $block, Facing $facing, Block $side) : bool{
		if($block instanceof Fence){
			$class = $block::class;
			return $side instanceof $class || $side instanceof FenceGate || $side->getSupportType(Facing::opposite($facing)) === SupportType::FULL;
		}
		return $side instanceof Thin || $side instanceof Wall || $side->getSupportType(Facing::opposite($facing)) === SupportType::FULL;
	}
}
