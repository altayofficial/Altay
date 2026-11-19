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

use pocketmine\player\Player;

/**
 * Blocks which open a menu when interacted with
 * This could be a container menu, or a menu that otherwise deals with items, such as a crafting menu
 */
interface MenuAccessor{
	/**
	 * Returns whether the block's ability to open the menu is currently obstructed (e.g. by nearby blocks).
	 */
	public function isOpeningObstructed() : bool;

	/**
	 * Opens the menu to the player.
	 * Note: No preconditions are checked. Do not check for obstruction or locks here.
	 *
	 * Returns true if successful, false otherwise (e.g. event cancelled, container missing)
	 */
	public function openToUnchecked(Player $player) : bool;
}
