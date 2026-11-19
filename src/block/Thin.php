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

namespace pocketmine\block;

use pocketmine\block\utils\HorizontalConnections;
use pocketmine\block\utils\HorizontalConnectionsTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use function count;

/**
 * Thin blocks behave like glass panes. They connect to full-cube blocks horizontally adjacent to them if possible.
 */
class Thin extends Transparent implements HorizontalConnections, StateDeriving{
	use HorizontalConnectionsTrait;

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();

		$this->collisionBoxes = null;

		return $this;
	}

	public function onNearbyBlockChange() : void{
		if($this->deriveStateFromWorld()){
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function deriveStateFromWorld() : bool{
		return $this->recalculateConnections();
	}

	protected function canConnectTo(int $facing) : bool{
		$side = $this->getSide($facing);
		return $side instanceof Thin || $side instanceof Wall || $side->getSupportType(Facing::opposite($facing)) === SupportType::FULL;
	}

	protected function recalculateCollisionBoxes() : array{
		$inset = 7 / 16;

		$bbs = [];

		if(isset($this->connections[Facing::WEST->value]) || isset($this->connections[Facing::EAST->value])){
			$bb = AxisAlignedBB::one()->squashedCopy(Axis::Z, $inset);

			if(!isset($this->connections[Facing::WEST->value])){
				$bb = $bb->trimmedCopy(Facing::WEST, $inset);
			}elseif(!isset($this->connections[Facing::EAST->value])){
				$bb = $bb->trimmedCopy(Facing::EAST, $inset);
			}
			$bbs[] = $bb;
		}

		if(isset($this->connections[Facing::NORTH->value]) || isset($this->connections[Facing::SOUTH->value])){
			$bb = AxisAlignedBB::one()->squashedCopy(Axis::X, $inset);

			if(!isset($this->connections[Facing::NORTH->value])){
				$bb = $bb->trimmedCopy(Facing::NORTH, $inset);
			}elseif(!isset($this->connections[Facing::SOUTH->value])){
				$bb = $bb->trimmedCopy(Facing::SOUTH, $inset);
			}
			$bbs[] = $bb;
		}

		if(count($bbs) === 0){
			//centre post AABB (only needed if not connected on any axis - other BBs overlapping will do this if any connections are made)
			return [
				AxisAlignedBB::one()->contractedCopy($inset, 0, $inset)
			];
		}

		return $bbs;
	}

	public function getSupportType(Facing $facing) : SupportType{
		return SupportType::NONE;
	}
}
