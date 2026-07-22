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

namespace pocketmine\block\tile;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;

interface Container extends InventoryHolder{
	public const TAG_ITEMS = "Items";
	public const TAG_LOCK = "Lock";

	public function getRealInventory() : Inventory;

	/**
	 * Returns whether this container can be opened by an item with the given custom name.
	 */
	public function canOpenWith(string $key) : bool;
}
