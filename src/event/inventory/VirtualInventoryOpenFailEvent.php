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

use pocketmine\inventory\VirtualInventory;
use pocketmine\inventory\VirtualInventoryOpenFailReason;
use pocketmine\player\Player;

/**
 * Called when a player's client refuses to show a virtual inventory, e.g. because another screen was already open.
 * The window gets removed right after this, so InventoryCloseEvent still follows as usual.
 */
class VirtualInventoryOpenFailEvent extends InventoryEvent{

	public function __construct(
		private VirtualInventory $virtualInventory,
		private Player $player,
		private VirtualInventoryOpenFailReason $reason
	){
		parent::__construct($virtualInventory);
	}

	public function getInventory() : VirtualInventory{
		return $this->virtualInventory;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getReason() : VirtualInventoryOpenFailReason{
		return $this->reason;
	}
}
