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

namespace pocketmine\block\utils;

use pocketmine\block\Water;

/**
 * Used by blocks which need water next to them to survive, such as coral and seagrass.
 */
trait CoveredWithWaterTrait{

	protected function isCoveredWithWater() : bool{
		$world = $this->position->getWorld();
		foreach($this->position->sides() as $vector3){
			if($world->getBlock($vector3) instanceof Water){
				return true;
			}
		}

		//TODO: check water inside the block itself (not supported on the API yet)
		return false;
	}
}
