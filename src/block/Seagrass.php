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

use pocketmine\block\utils\SeagrassType;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use function mt_rand;

class Seagrass extends Transparent{

	protected SeagrassType $seagrassType = SeagrassType::NORMAL;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->seagrassType);
	}

	public function getSeagrassType() : SeagrassType{
		return $this->seagrassType;
	}

	/** @return $this */
	public function setSeagrassType(SeagrassType $seagrassType) : self{
		$this->seagrassType = $seagrassType;
		return $this;
	}

	public function isSolid() : bool{
		return false;
	}

	public function canBeReplaced() : bool{
		return $this->seagrassType === SeagrassType::NORMAL;
	}

	protected function recalculateCollisionBoxes() : array{
		return [];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if(!$blockReplace instanceof Water || !$this->canSurviveOn($blockReplace->getSide(Facing::DOWN))){
			return false;
		}

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onNearbyBlockChange() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, mt_rand(40, 200));
	}

	public function onScheduledUpdate() : void{
		if(!$this->isCoveredWithWater() || !$this->hasValidSupport()){
			$this->position->getWorld()->useBreakOn($this->position);
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if(!$item instanceof Fertilizer || $this->seagrassType !== SeagrassType::NORMAL){
			return false;
		}

		$up = $this->getSide(Facing::UP);
		if(!$up instanceof Water){
			return false;
		}

		$world = $this->position->getWorld();
		$world->setBlock($this->position, (clone $this)->setSeagrassType(SeagrassType::DOUBLE_BOTTOM));
		$world->setBlock($up->position, (clone $this)->setSeagrassType(SeagrassType::DOUBLE_TOP));
		$item->pop();

		return true;
	}

	public function getDrops(Item $item) : array{
		if($this->seagrassType === SeagrassType::DOUBLE_TOP){
			return [];
		}

		return [$this->asItem()];
	}

	private function hasValidSupport() : bool{
		if($this->seagrassType === SeagrassType::DOUBLE_TOP){
			$down = $this->getSide(Facing::DOWN);
			return $down instanceof Seagrass && $down->seagrassType === SeagrassType::DOUBLE_BOTTOM;
		}

		if(!$this->canSurviveOn($this->getSide(Facing::DOWN))){
			return false;
		}

		if($this->seagrassType === SeagrassType::DOUBLE_BOTTOM){
			$up = $this->getSide(Facing::UP);
			return $up instanceof Seagrass && $up->seagrassType === SeagrassType::DOUBLE_TOP;
		}

		return true;
	}

	private function canSurviveOn(Block $block) : bool{
		return $block->isSolid()
			&& $block->getTypeId() !== BlockTypeIds::MAGMA
			&& $block->getTypeId() !== BlockTypeIds::SOUL_SAND;
	}

	private function isCoveredWithWater() : bool{
		$world = $this->position->getWorld();
		foreach($this->position->sides() as $side){
			if($world->getBlock($side) instanceof Water){
				return true;
			}
		}

		//TODO: check water inside the block itself (not supported on the API yet)
		return false;
	}
}
