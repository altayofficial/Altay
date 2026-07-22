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

use pocketmine\block\Campfire;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\item\Item;

class CampfireCookEvent extends BlockEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		private Campfire $campfire,
		private int $slot,
		private Item $input,
		private Item $result
	){
		parent::__construct($campfire);
		$this->input = clone $input;
	}

	public function getCampfire() : Campfire{
		return $this->campfire;
	}

	public function getSlot() : int{
		return $this->slot;
	}

	public function getInput() : Item{
		return $this->input;
	}

	public function getResult() : Item{
		return $this->result;
	}

	public function setResult(Item $result) : void{
		$this->result = $result;
	}
}
