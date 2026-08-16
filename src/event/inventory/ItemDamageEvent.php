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
use pocketmine\item\Durable;

/**
 * Called when an item is damaged
 */
class ItemDamageEvent extends Event implements Cancellable{
	use CancellableTrait;

	public const CAUSE_ENTITY_ATTACK = 0;
	public const CAUSE_BLOCK_BREAK = 1;
	public const CAUSE_ARMOR_DAMAGE = 2;
	public const CAUSE_OTHER = 3;

	public function __construct(
		private Living $entity,
		private Durable $item,
		private int $damage,
		private int $cause,
		private Entity|Block|null $target = null
	){
	}

	public function getEntity() : Living{
		return $this->entity;
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

	public function getCause() : int{
		return $this->cause;
	}

	public function getTarget() : Entity|Block|null{
		return $this->target;
	}
}