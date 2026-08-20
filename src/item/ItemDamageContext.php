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

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\inventory\Inventory;

final class ItemDamageContext{

	public function __construct(
		private int $cause,
		private ?Living $entity = null,
		private Block|Entity|null $target = null,
		private ?Inventory $inventory = null,
		private ?int $slot = null
	){
	}

	public function getCause() : int{
		return $this->cause;
	}

	public function getEntity() : ?Living{
		return $this->entity;
	}

	public function getTarget() : Block|Entity|null{
		return $this->target;
	}

	public function getInventory() : ?Inventory{
		return $this->inventory;
	}

	public function getSlot() : ?int{
		return $this->slot;
	}
}
