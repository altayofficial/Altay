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

namespace pocketmine\event\inventory;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

/**
 * Called when an item is moved into an inventory by a block, such as a hopper.
 *
 * The inventory of this event is the one receiving the item. Some sources, such as jukeboxes, don't have an inventory
 * at all, in which case the source is null.
 */
class InventoryMoveItemEvent extends InventoryEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		private ?Inventory $source,
		Inventory $destination,
		private Item $item
	){
		parent::__construct($destination);
	}

	/**
	 * Returns the inventory the item is taken from, or null if the item doesn't come from an inventory.
	 */
	public function getSource() : ?Inventory{
		return $this->source;
	}

	public function getDestination() : Inventory{
		return $this->inventory;
	}

	/**
	 * Returns the item which is being moved.
	 */
	public function getItem() : Item{
		return clone $this->item;
	}

	/**
	 * Changes the item which is moved to the destination inventory.
	 */
	public function setItem(Item $item) : void{
		$this->item = clone $item;
	}
}
