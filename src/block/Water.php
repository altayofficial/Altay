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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityExtinguishEvent;
use pocketmine\world\sound\BucketEmptyWaterSound;
use pocketmine\world\sound\BucketFillWaterSound;
use pocketmine\world\sound\Sound;

class Water extends Liquid{

	public function getLightFilter() : int{
		return 2;
	}

	public function getBucketFillSound() : Sound{
		return new BucketFillWaterSound();
	}

	public function getBucketEmptySound() : Sound{
		return new BucketEmptyWaterSound();
	}

	public function tickRate() : int{
		return 5;
	}

	public function getMinAdjacentSourcesToFormSource() : ?int{
		return 2;
	}

	protected function getFlowResult(Block $target, int $newFlowDecay, bool $falling) : Block{
		$new = parent::getFlowResult($target, $newFlowDecay, $falling);
		if($this->canWaterlog($target)){
			$target = clone $target;
			$containedWater = clone $this;
			$containedWater->falling = false;
			$containedWater->decay = 0;
			return $target->setContainedWater($containedWater);
		}
		return $new;
	}

	protected function getDecayResult(Block $oldForm) : Block{
		return $oldForm->canBeWaterlogged() ? $oldForm->setContainedWater(null) : parent::getDecayResult($oldForm);
	}

	protected function isSideAvailable(Block $block, int $face) : bool{
		return !$block->canBeWaterlogged() || $block->isSideOpenToWaterFlow($face);
	}

	protected function unpackLiquid(Block $block) : Block{
		return $block->canBeWaterlogged() ? ($block->getContainedWater() ?? $block) : $block;
	}

	protected function canFlowInto(Block $block) : bool{
		if($this->canWaterlog($block)){
			return $this->position->getWorld()->isInWorld($block->position->x, $block->position->y, $block->position->z);
		}
		return parent::canFlowInto($block);
	}

	private function canWaterlog(Block $block) : bool{
		return
			$block->canBeWaterlogged() &&
			$block->getContainedWater() === null &&
			($this->isSource() || $block->canBeWaterloggedByNonSource());
	}

	public function onEntityInside(Entity $entity) : bool{
		$entity->resetFallDistance();
		if($entity->isOnFire()){
			$entity->extinguish(EntityExtinguishEvent::CAUSE_WATER);
		}
		return true;
	}
}
