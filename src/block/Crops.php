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

use pocketmine\block\utils\Ageable;
use pocketmine\block\utils\AgeableTrait;
use pocketmine\block\utils\BlockEventHelper;
use pocketmine\block\utils\CropGrowthHelper;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function mt_rand;

abstract class Crops extends Flowable implements Ageable{
	use AgeableTrait;
	use StaticSupportTrait;

	public const MAX_AGE = 7;

	private function canBeSupportedAt(Block $block) : bool{
		return $block->getSide(Facing::DOWN)->getTypeId() === BlockTypeIds::FARMLAND;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->age < self::MAX_AGE && $item instanceof Fertilizer){
			$block = clone $this;
			$tempAge = $block->age + mt_rand(2, 5);
			if($tempAge > self::MAX_AGE){
				$tempAge = self::MAX_AGE;
			}
			$block->age = $tempAge;
			if(BlockEventHelper::grow($this, $block, $player)){
				$item->pop();
			}

			return true;
		}

		return false;
	}

	public function ticksRandomly() : bool{
		return $this->age < self::MAX_AGE;
	}

	public function onRandomTick() : void{
		if($this->age < self::MAX_AGE && CropGrowthHelper::canGrow($this)){
			$block = clone $this;
			++$block->age;
			BlockEventHelper::grow($this, $block, null);
		}
	}
}
