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

use pocketmine\item\Item;

final class CreativeInventoryEntry{
	private readonly Item $item;

	public function __construct(
		Item $item,
		private readonly CreativeCategory $category,
		private readonly ?CreativeGroup $group = null
	){
		$this->item = clone $item;
	}

	public function getItem() : Item{ return clone $this->item; }

	public function getCategory() : CreativeCategory{ return $this->category; }

	public function getGroup() : ?CreativeGroup{ return $this->group; }

	public function matchesItem(Item $item) : bool{
		return $item->equals($this->item, checkDamage: true, checkCompound: false);
	}
}
