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

namespace pocketmine\item;

use pocketmine\entity\Living;
use pocketmine\player\Player;

class MilkBucket extends Item implements ConsumableItem{

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getResidue() : Item{
		return VanillaItems::BUCKET();
	}

	public function getAdditionalEffects() : array{
		return [];
	}

	public function onConsume(Living $consumer) : void{
		$consumer->getEffects()->clear();
	}

	public function canStartUsingItem(Player $player) : bool{
		return true;
	}

	public function getMinUseDuration() : int{
		return 32;
	}
}
