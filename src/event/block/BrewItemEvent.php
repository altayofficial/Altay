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

namespace pocketmine\event\block;

use pocketmine\block\tile\BrewingStand;
use pocketmine\crafting\BrewingRecipe;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\item\Item;

class BrewItemEvent extends BlockEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		private BrewingStand $brewingStand,
		private int $slot,
		private Item $input,
		private Item $result,
		private BrewingRecipe $recipe
	){
		parent::__construct($brewingStand->getBlock());
	}

	public function getBrewingStand() : BrewingStand{
		return $this->brewingStand;
	}

	/**
	 * Returns which slot of the brewing stand's inventory the potion is in.
	 */
	public function getSlot() : int{
		return $this->slot;
	}

	public function getInput() : Item{
		return clone $this->input;
	}

	public function getResult() : Item{
		return clone $this->result;
	}

	public function setResult(Item $result) : void{
		$this->result = clone $result;
	}

	public function getRecipe() : BrewingRecipe{
		return $this->recipe;
	}
}
