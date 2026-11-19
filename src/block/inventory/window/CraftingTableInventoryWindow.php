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

namespace pocketmine\block\inventory\window;

use pocketmine\crafting\CraftingGrid;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class CraftingTableInventoryWindow extends BlockInventoryWindow{

	public function __construct(
		Player $viewer,
		Position $holder
	){
		//TODO: generics would be good for this, since it has special methods
		parent::__construct($viewer, new CraftingGrid(CraftingGrid::SIZE_BIG), $holder);
	}
}
