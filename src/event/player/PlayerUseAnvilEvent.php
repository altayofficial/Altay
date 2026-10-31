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

namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\inventory\transaction\AnvilTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

/**
 * Called when a player takes the result of repairing, combining or renaming items in an anvil.
 */
class PlayerUseAnvilEvent extends PlayerEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		Player $player,
		private readonly AnvilTransaction $transaction,
		private readonly Item $inputItem,
		private readonly Item $materialItem,
		private readonly Item $outputItem,
		private readonly int $cost
	){
		$this->player = $player;
	}

	/**
	 * Returns the inventory transaction involved in this event.
	 */
	public function getTransaction() : AnvilTransaction{
		return $this->transaction;
	}

	/**
	 * Returns the item placed in the input slot of the anvil.
	 */
	public function getInputItem() : Item{
		return clone $this->inputItem;
	}

	/**
	 * Returns the part of the material slot consumed by the operation. The item is air when only renaming.
	 */
	public function getMaterialItem() : Item{
		return clone $this->materialItem;
	}

	/**
	 * Returns the resulting item.
	 */
	public function getOutputItem() : Item{
		return clone $this->outputItem;
	}

	/**
	 * Returns the number of XP levels that will be subtracted if the player is not in creative mode.
	 */
	public function getCost() : int{
		return $this->cost;
	}
}
