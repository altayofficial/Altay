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

namespace pocketmine\inventory;

use pocketmine\inventory\transaction\action\filter\SlotChangeFilter;
use pocketmine\utils\ObjectSet;

/**
 * An inventory whose slot changes can be vetoed per player by filters, e.g. to turn it into a read only menu.
 * Filters run after InventoryTransactionEvent, and if any of them refuses, the whole transaction is cancelled.
 *
 * There is no guarantee that the filters will be called in any particular order.
 *
 * @phpstan-type SlotChangeFilters ObjectSet<SlotChangeFilter>
 */
interface SlotChangeFilteredInventory extends Inventory{
	/**
	 * @phpstan-return SlotChangeFilters
	 */
	public function getSlotChangeFilters() : ObjectSet;
}
