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

use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use function mt_rand;

final class ChorusPlant extends Flowable implements StateDeriving{
	use StaticSupportTrait;

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	protected array $connections = [];

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facingFlags($this->connections);
	}

	public function isConnected(int $facing) : bool{
		return isset($this->connections[$facing]);
	}

	/** @return $this */
	public function setConnected(int $facing, bool $connected) : self{
		if($connected){
			$this->connections[$facing] = $facing;
		}else{
			unset($this->connections[$facing]);
		}
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$bb = AxisAlignedBB::one();
		foreach(Facing::ALL as $facing){
			if(!isset($this->connections[$facing])){
				$bb->trim($facing, 2 / 16);
			}
		}

		return [$bb];
	}

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();

		$this->collisionBoxes = null;

		return $this;
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		if(!$this->canBeSupportedAt($this)){
			$world->useBreakOn($this->position);
		}elseif($this->deriveStateFromWorld()){
			$world->setBlock($this->position, $this);
		}
	}

	public function deriveStateFromWorld() : bool{
		$changed = false;
		foreach(Facing::ALL as $facing){
			$connected = match($this->getSide($facing)->getTypeId()){
				BlockTypeIds::END_STONE, BlockTypeIds::CHORUS_FLOWER, $this->getTypeId() => true,
				default => false
			};
			if($connected !== isset($this->connections[$facing])){
				$this->setConnected($facing, $connected);
				$changed = true;
			}
		}
		if($changed){
			$this->collisionBoxes = null;
		}
		return $changed;
	}

	private function canBeSupportedBy(Block $block) : bool{
		return $block->hasSameTypeId($this) || $block->getTypeId() === BlockTypeIds::END_STONE;
	}

	private function canBeSupportedAt(Block $block) : bool{
		$position = $block->position;
		$world = $position->getWorld();

		$down = $world->getBlock($position->down());
		$verticalAir = $down->getTypeId() === BlockTypeIds::AIR || $world->getBlock($position->up())->getTypeId() === BlockTypeIds::AIR;

		foreach($position->sidesAroundAxis(Axis::Y) as $sidePosition){
			$block = $world->getBlock($sidePosition);

			if($block->getTypeId() === BlockTypeIds::CHORUS_PLANT){
				if(!$verticalAir){
					return false;
				}

				if($this->canBeSupportedBy($block->getSide(Facing::DOWN))){
					return true;
				}
			}
		}

		return $this->canBeSupportedBy($down);
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		if(mt_rand(0, 1) === 1){
			return [VanillaItems::CHORUS_FRUIT()];
		}

		return [];
	}
}
