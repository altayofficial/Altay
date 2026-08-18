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

namespace pocketmine\world\generator\object;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\utils\Random;
use pocketmine\world\BlockTransaction;
use pocketmine\world\ChunkManager;
use function abs;
use function max;

class CherryTree extends Tree{
	private const LEAVES_RADIUS = 4;

	private Block $trunkBlockX;
	private Block $trunkBlockZ;

	public function __construct(){
		parent::__construct(VanillaBlocks::CHERRY_LOG(), VanillaBlocks::CHERRY_LEAVES());

		$this->trunkBlockX = VanillaBlocks::CHERRY_LOG()->setAxis(Axis::X);
		$this->trunkBlockZ = VanillaBlocks::CHERRY_LOG()->setAxis(Axis::Z);
	}

	public function getBlockTransaction(ChunkManager $world, int $x, int $y, int $z, Random $random) : ?BlockTransaction{
		if($random->nextBoolean()){
			$transaction = new BlockTransaction($world);
			if($this->placeBigTree($x, $y, $z, $random, $transaction)){
				return $transaction;
			}
		}

		$transaction = new BlockTransaction($world);
		return $this->placeSmallTree($x, $y, $z, $random, $transaction) ? $transaction : null;
	}

	private function placeBigTree(int $x, int $y, int $z, Random $random, BlockTransaction $transaction) : bool{
		$mainTrunkHeight = 10 + ($random->nextBoolean() ? 1 : 0);
		if(!$this->canPlaceTrunk($transaction, $mainTrunkHeight, $x, $y, $z)){
			return false;
		}

		$growOnXAxis = $random->nextBoolean();

		$leftLength = $random->nextRange(2, 4);
		$leftHeight = $random->nextRange(3, 5);
		$leftStartY = $random->nextRange(4, 5);

		[$xMultiplier, $zMultiplier] = $growOnXAxis ? [1, 0] : [0, 1];
		if(!$this->canPlaceTrunk($transaction, $leftHeight, $x - $leftLength * $xMultiplier, $y + $leftStartY, $z - $leftLength * $zMultiplier)){
			//the preferred axis is blocked, try growing the branches along the other one
			$growOnXAxis = !$growOnXAxis;
			[$xMultiplier, $zMultiplier] = $growOnXAxis ? [1, 0] : [0, 1];
			if(!$this->canPlaceTrunk($transaction, $leftHeight, $x - $leftLength * $xMultiplier, $y + $leftStartY, $z - $leftLength * $zMultiplier)){
				return false;
			}
		}

		$rightLength = $random->nextRange(2, 4);
		$rightHeight = $random->nextRange(3, 5);
		$rightStartY = $random->nextRange(4, 5);
		if(!$this->canPlaceTrunk($transaction, $rightHeight, $x + $rightLength * $xMultiplier, $y + $rightStartY, $z + $rightLength * $zMultiplier)){
			return false;
		}

		$transaction->addBlockAt($x, $y - 1, $z, VanillaBlocks::DIRT());
		for($yy = 0; $yy < $mainTrunkHeight; ++$yy){
			$transaction->addBlockAt($x, $y + $yy, $z, $this->trunkBlock);
		}

		$sideTrunkBlock = $growOnXAxis ? $this->trunkBlockX : $this->trunkBlockZ;
		$this->placeBranch($transaction, $x, $y, $z, -$xMultiplier, -$zMultiplier, $leftLength, $leftHeight, $leftStartY, $sideTrunkBlock);
		$this->placeBranch($transaction, $x, $y, $z, $xMultiplier, $zMultiplier, $rightLength, $rightHeight, $rightStartY, $sideTrunkBlock);

		$this->placeLeaves($transaction, $random, $x, $y + $mainTrunkHeight + 1, $z);
		$this->placeLeaves($transaction, $random, $x - $leftLength * $xMultiplier, $y + $leftStartY + $leftHeight + 1, $z - $leftLength * $zMultiplier);
		$this->placeLeaves($transaction, $random, $x + $rightLength * $xMultiplier, $y + $rightStartY + $rightHeight + 1, $z + $rightLength * $zMultiplier);

		return true;
	}

	private function placeBranch(BlockTransaction $transaction, int $x, int $y, int $z, int $xStep, int $zStep, int $length, int $height, int $startY, Block $sideTrunkBlock) : void{
		for($offset = 1; $offset <= $length; ++$offset){
			$this->placeTrunkBlock($transaction, $x + $offset * $xStep, $y + $startY, $z + $offset * $zStep, $sideTrunkBlock);
		}
		for($yy = 1; $yy < $height; ++$yy){
			$this->placeTrunkBlock($transaction, $x + $length * $xStep, $y + $startY + $yy, $z + $length * $zStep, $this->trunkBlock);
		}

		//branches starting low are bent one block upwards at their end instead of turning straight up
		if($startY === 4){
			$endX = $x + $length * $xStep;
			$endZ = $z + $length * $zStep;
			$transaction->addBlockAt($endX, $y + $startY, $endZ, VanillaBlocks::AIR());
			$this->placeTrunkBlock($transaction, $endX - $xStep, $y + $startY + 1, $endZ - $zStep, $this->trunkBlock);
			$this->placeTrunkBlock($transaction, $endX, $y + $startY + 1, $endZ, $sideTrunkBlock);
		}
	}

	private function placeSmallTree(int $x, int $y, int $z, Random $random, BlockTransaction $transaction) : bool{
		$mainTrunkHeight = 4 + ($random->nextBoolean() ? 1 : 0);
		$sideTrunkHeight = $random->nextRange(3, 5);

		if(!$this->canPlaceTrunk($transaction, $mainTrunkHeight + 1, $x, $y, $z)){
			return false;
		}

		$direction = $random->nextRange(0, 3);
		$xStep = 0;
		$zStep = 0;
		$found = false;
		for($i = 0; $i < 4; ++$i){
			$direction = ($direction + 1) % 4;
			$xStep = match($direction){
				0 => -1,
				1 => 1,
				default => 0,
			};
			$zStep = match($direction){
				2 => -1,
				3 => 1,
				default => 0,
			};
			if($this->canPlaceTrunk($transaction, $sideTrunkHeight, $x + $xStep * $sideTrunkHeight, $y, $z + $zStep * $sideTrunkHeight)){
				$found = true;
				break;
			}
		}
		if(!$found){
			return false;
		}

		$transaction->addBlockAt($x, $y - 1, $z, VanillaBlocks::DIRT());
		for($yy = 0; $yy < $mainTrunkHeight; ++$yy){
			$this->placeTrunkBlock($transaction, $x, $y + $yy, $z, $this->trunkBlock);
		}

		//the branch climbs diagonally away from the trunk, one block sideways and one block up at a time
		$sideTrunkBlock = $xStep === 0 ? $this->trunkBlockZ : $this->trunkBlockX;
		for($yy = 1; $yy <= $sideTrunkHeight; ++$yy){
			$branchX = $x + $yy * $xStep;
			$branchY = $y + $mainTrunkHeight + $yy - 2;
			$branchZ = $z + $yy * $zStep;
			$this->placeTrunkBlock($transaction, $branchX, $branchY, $branchZ, $sideTrunkBlock);

			//long branches flatten out just before their end
			if($yy === $sideTrunkHeight - 1 && $sideTrunkHeight > 3){
				continue;
			}
			$this->placeTrunkBlock($transaction, $branchX, $branchY + 1, $branchZ, $this->trunkBlock);
		}

		$this->placeLeaves($transaction, $random, $x + $sideTrunkHeight * $xStep, $y + $mainTrunkHeight + $sideTrunkHeight, $z + $sideTrunkHeight * $zStep);

		return true;
	}

	private function placeTrunkBlock(BlockTransaction $transaction, int $x, int $y, int $z, Block $block) : void{
		if($this->canOverride($transaction->fetchBlockAt($x, $y, $z))){
			$transaction->addBlockAt($x, $y, $z, $block);
		}
	}

	private function placeLeaves(BlockTransaction $transaction, Random $random, int $x, int $y, int $z) : void{
		for($dy = -2; $dy <= 2; ++$dy){
			$radius = self::LEAVES_RADIUS - max(1, abs($dy));
			for($dx = -self::LEAVES_RADIUS; $dx <= self::LEAVES_RADIUS; ++$dx){
				for($dz = -self::LEAVES_RADIUS; $dz <= self::LEAVES_RADIUS; ++$dz){
					if($dx * $dx + $dz * $dz > $radius * $radius){ // yeah...
						continue;
					}
					$this->placeLeafBlock($transaction, $x + $dx, $y + $dy, $z + $dz);

					//the bottom layer is frayed rather than flat
					if($dy === -2 && $random->nextRange(0, 2) === 0){
						$this->placeLeafBlock($transaction, $x + $dx, $y + $dy - 1, $z + $dz);
					}
				}
			}
		}
	}

	private function placeLeafBlock(BlockTransaction $transaction, int $x, int $y, int $z) : void{
		if($this->canOverride($transaction->fetchBlockAt($x, $y, $z))){
			$transaction->addBlockAt($x, $y, $z, $this->leafBlock);
		}
	}

	private function canPlaceTrunk(BlockTransaction $transaction, int $height, int $x, int $y, int $z) : bool{
		$radiusToCheck = 0;
		for($yy = 0; $yy < $height + 3; ++$yy){
			if($yy === 1 || $yy === $height){
				++$radiusToCheck;
			}
			for($xx = -$radiusToCheck; $xx <= $radiusToCheck; ++$xx){
				for($zz = -$radiusToCheck; $zz <= $radiusToCheck; ++$zz){
					if(!$this->canOverride($transaction->fetchBlockAt($x + $xx, $y + $yy, $z + $zz))){
						return false;
					}
				}
			}
		}

		return true;
	}
}
