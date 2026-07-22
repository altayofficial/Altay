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
use pocketmine\block\utils\FortuneDropHelper;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use function mt_rand;

class NetherWartPlant extends Flowable implements Ageable{
	use AgeableTrait;
	use StaticSupportTrait;

	public const MAX_AGE = 3;

	private function canBeSupportedAt(Block $block) : bool{
		return $block->getSide(Facing::DOWN)->getTypeId() === BlockTypeIds::SOUL_SAND;
	}

	public function ticksRandomly() : bool{
		return $this->age < self::MAX_AGE;
	}

	public function onRandomTick() : void{
		if($this->age < self::MAX_AGE && mt_rand(0, 10) === 0){ //Still growing
			$block = clone $this;
			$block->age++;
			BlockEventHelper::grow($this, $block, null);
		}
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			$this->asItem()->setCount($this->age === self::MAX_AGE ? FortuneDropHelper::discrete($item, 2, 4) : 1)
		];
	}
}
