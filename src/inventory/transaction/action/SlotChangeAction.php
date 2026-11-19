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

namespace pocketmine\inventory\transaction\action;

use pocketmine\inventory\SlotValidatedInventory;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use pocketmine\item\ItemLockMode;
use pocketmine\player\InventoryWindow;
use pocketmine\player\Player;
use pocketmine\player\PlayerInventoryWindow;

/**
 * Represents an action causing a change in an inventory slot.
 */
class SlotChangeAction extends InventoryAction{
	public function __construct(
		protected InventoryWindow $inventoryWindow,
		private int $inventorySlot,
		Item $sourceItem,
		Item $targetItem
	){
		parent::__construct($sourceItem, $targetItem);
	}

	/**
	 * Returns the inventory window involved in this action.
	 */
	public function getInventoryWindow() : InventoryWindow{
		return $this->inventoryWindow;
	}

	/**
	 * Returns the slot in the inventory which this action modified.
	 */
	public function getSlot() : int{
		return $this->inventorySlot;
	}

	/**
	 * Checks if the item in the inventory at the specified slot is the same as this action's source item.
	 *
	 * @throws TransactionValidationException
	 */
	public function validate(Player $source) : void{
		$inventory = $this->inventoryWindow->getInventory();
		if(!$inventory->slotExists($this->inventorySlot)){
			throw new TransactionValidationException("Slot does not exist");
		}
		if(!$inventory->getItem($this->inventorySlot)->equalsExact($this->sourceItem)){
			throw new TransactionValidationException("Slot does not contain expected original item");
		}
		if($this->targetItem->getCount() > $this->targetItem->getMaxStackSize()){
			throw new TransactionValidationException("Target item exceeds item type max stack size");
		}
		if($this->targetItem->getCount() > $inventory->getMaxStackSize()){
			throw new TransactionValidationException("Target item exceeds inventory max stack size");
		}
		if(
			$this->sourceItem->getLockMode() === ItemLockMode::PLAYER_INVENTORY_SLOT &&
			$this->inventoryWindow instanceof PlayerInventoryWindow &&
			$this->inventoryWindow->getType() === PlayerInventoryWindow::TYPE_INVENTORY
		){
			throw new TransactionValidationException("Source item is locked in slot");
		}
		if($inventory instanceof SlotValidatedInventory && !$this->targetItem->isNull()){
			foreach($inventory->getSlotValidators() as $validator){
				$ret = $validator->validate($inventory, $this->targetItem, $this->inventorySlot);
				if($ret !== null){
					throw new TransactionValidationException("Target item is not accepted by the inventory at slot #" . $this->inventorySlot . ": " . $ret->getMessage(), 0, $ret);
				}
			}
		}
	}

	/**
	 * Sets the item into the target inventory.
	 */
	public function execute(Player $source) : void{
		$this->inventoryWindow->getInventory()->setItem($this->inventorySlot, $this->targetItem);
	}
}
