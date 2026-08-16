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

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\inventory\Inventory;
use pocketmine\item\Durable;

/**
 * Called when an item is damaged
 */
class ItemDamageEvent extends Event implements Cancellable{
	use CancellableTrait;

	public const CAUSE_PLUGIN = 0;
	public const CAUSE_BLOCK_BREAK = 1;
	public const CAUSE_ENTITY_ATTACK = 2;
	public const CAUSE_ARMOR_DAMAGE = 3;
	public const CAUSE_THORNS = 4;
	public const CAUSE_BLOCK_INTERACT = 5;
	public const CAUSE_PROJECTILE = 6;

	public function __construct(
		private Durable $item,
		private int $damage,
		private int $unbreakingDamageReduction = 0,
		private int $cause = self::CAUSE_PLUGIN,
		private ?Living $entity = null,
		private Block|Entity|null $target = null,
		private ?Inventory $inventory = null,
		private ?int $slot = null
	){
	}

	public function getItem() : Durable{
		return $this->item;
	}

	public function getDamage() : int{
		return $this->damage;
	}

	public function setDamage(int $damage) : void{
		$this->damage = $damage;
	}

	public function getUnbreakingDamageReduction() : int{
		return $this->unbreakingDamageReduction;
	}

	public function setUnbreakingDamageReduction(int $unbreakingDamageReduction) : void{
		$this->unbreakingDamageReduction = $unbreakingDamageReduction;
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