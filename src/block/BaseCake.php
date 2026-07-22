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
use pocketmine\block\utils\SupportType;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\FoodSource;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

abstract class BaseCake extends Transparent implements FoodSource{
	use StaticSupportTrait;

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	private function canBeSupportedAt(Block $block) : bool{
		return $block->getSide(Facing::DOWN)->getTypeId() !== BlockTypeIds::AIR;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			return $player->consumeObject($this);
		}

		return false;
	}

	public function getFoodRestore() : int{
		return 2;
	}

	public function getSaturationRestore() : float{
		return 0.4;
	}

	public function requiresHunger() : bool{
		return true;
	}

	/**
	 * @return EffectInstance[]
	 */
	public function getAdditionalEffects() : array{
		return [];
	}

	abstract public function getResidue() : Block;

	public function onConsume(Living $consumer) : void{
		$this->position->getWorld()->setBlock($this->position, $this->getResidue());
	}
}
