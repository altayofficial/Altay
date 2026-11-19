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

use pocketmine\block\utils\HorizontalFacingOption;
use pocketmine\item\Item;
use pocketmine\item\Shears;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Pumpkin extends Opaque{

	public function onInteract(Item $item, Facing $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($item instanceof Shears && ($hzFacing = HorizontalFacingOption::tryFromFacing($face)) !== null){
			$item->applyDamage(1);
			$world = $this->position->getWorld();
			$world->setBlock($this->position, VanillaBlocks::CARVED_PUMPKIN()->setFacing($hzFacing));
			$world->dropItem($this->position->add(0.5, 0.5, 0.5), VanillaItems::PUMPKIN_SEEDS()->setCount(1));
			return true;
		}
		return false;
	}
}
