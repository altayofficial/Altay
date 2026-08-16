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
use pocketmine\event\inventory\ItemDamageEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Utils;
use function min;

abstract class Durable extends Item{
	protected int $damage = 0;
	private bool $unbreakable = false;

	private ?int $damageContextCause = null;
	private ?Living $damageContextEntity = null;
	private Block|Entity|null $damageContextTarget = null;
	private ?Inventory $damageContextInventory = null;
	private ?int $damageContextSlot = null;

	/**
	 * Returns whether this item will take damage when used.
	 */
	public function isUnbreakable() : bool{
		return $this->unbreakable;
	}

	/**
	 * Sets whether the item will take damage when used.
	 *
	 * @return $this
	 */
	public function setUnbreakable(bool $value = true) : self{
		$this->unbreakable = $value;
		return $this;
	}

	/**
	 * Applies damage to the item without any specific usage context.
	 *
	 * @return bool if any damage was applied to the item
	 */
	public function applyDamage(int $amount) : bool{
		if($this->damageContextCause !== null){
			return $this->applyDamageWithContext(
				$amount,
				$this->damageContextCause,
				$this->damageContextEntity,
				$this->damageContextTarget,
				$this->damageContextInventory,
				$this->damageContextSlot
			);
		}

		return $this->applyDamageWithContext(
			$amount,
			ItemDamageEvent::CAUSE_PLUGIN
		);
	}

	/**
	 * Applies damage to the item with information about how and where
	 * the item was damaged.
	 *
	 * @return bool if any damage was applied to the item
	 */
	public function applyDamageWithContext(
		int $amount,
		int $cause,
		?Living $entity = null,
		Block|Entity|null $target = null,
		?Inventory $inventory = null,
		?int $slot = null
	) : bool{
		if($this->isUnbreakable() || $this->isBroken()){
			return false;
		}

		$unbreakingDamageReduction = $this->getUnbreakingDamageReduction($amount);

		$event = new ItemDamageEvent(
			$this,
			$amount,
			$unbreakingDamageReduction,
			$cause,
			$entity,
			$target,
			$inventory,
			$slot
		);
		$event->call();

		if($event->isCancelled()){
			return false;
		}

		$finalDamage = max(
			0,
			$event->getDamage() - $event->getUnbreakingDamageReduction()
		);

		return $this->applyRawDamage($finalDamage);
	}

	/**
	 * Applies already calculated durability damage.
	 */
	private function applyRawDamage(int $amount) : bool{
		$this->damage = min(
			$this->damage + $amount,
			$this->getMaxDurability()
		);

		if($this->isBroken()){
			$this->onBroken();
		}

		return true;
	}

	public function getDamage() : int{
		return $this->damage;
	}

	public function setDamage(int $damage) : Item{
		if($damage < 0 || $damage > $this->getMaxDurability()){
			throw new \InvalidArgumentException("Damage must be in range 0 - " . $this->getMaxDurability());
		}
		$this->damage = $damage;
		return $this;
	}

	protected function getUnbreakingDamageReduction(int $amount) : int{
		if(($unbreakingLevel = $this->getEnchantmentLevel(VanillaEnchantments::UNBREAKING())) > 0){
			$negated = 0;

			$chance = 1 / ($unbreakingLevel + 1);
			for($i = 0; $i < $amount; ++$i){
				if(Utils::getRandomFloat() > $chance){
					$negated++;
				}
			}

			return $negated;
		}

		return 0;
	}

	/**
	 * Called when the item's damage exceeds its maximum durability.
	 */
	protected function onBroken() : void{
		$this->pop();
		$this->setDamage(0); //the stack size may be greater than 1 if overstacked by a plugin
	}

	/**
	 * Returns the maximum amount of damage this item can take before it breaks.
	 */
	abstract public function getMaxDurability() : int;

	/**
	 * Returns whether the item is broken.
	 */
	public function isBroken() : bool{
		return $this->damage >= $this->getMaxDurability() || $this->isNull();
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);
		$this->unbreakable = $tag->getByte("Unbreakable", 0) !== 0;

		$damage = $tag->getInt("Damage", $this->damage);
		if($damage !== $this->damage && $damage >= 0 && $damage <= $this->getMaxDurability()){
			//TODO: out-of-bounds damage should be an error
			$this->setDamage($damage);
		}
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);
		$this->unbreakable ? $tag->setByte("Unbreakable", 1) : $tag->removeTag("Unbreakable");
		$this->damage !== 0 ? $tag->setInt("Damage", $this->damage) : $tag->removeTag("Damage");
	}

	public function onAttackEntityWithContext(Entity $victim, Living $entity, ?Inventory $inventory, ?int $slot, array &$returnedItems) : bool{
		$this->damageContextCause = ItemDamageEvent::CAUSE_ENTITY_ATTACK;
		$this->damageContextEntity = $entity;
		$this->damageContextTarget = $victim;
		$this->damageContextInventory = $inventory;
		$this->damageContextSlot = $slot;

		try{
			return $this->onAttackEntity($victim, $returnedItems);
		}finally{
			$this->clearDamageContext();
		}
	}

	public function onDestroyBlockWithContext(Block $block, ?Living $entity, ?Inventory $inventory, ?int $slot, array &$returnedItems) : bool{
		$this->damageContextCause = ItemDamageEvent::CAUSE_BLOCK_BREAK;
		$this->damageContextEntity = $entity;
		$this->damageContextTarget = $block;
		$this->damageContextInventory = $inventory;
		$this->damageContextSlot = $slot;

		try{
			return $this->onDestroyBlock($block, $returnedItems);
		}finally{
			$this->clearDamageContext();
		}
	}

	private function clearDamageContext() : void{
		$this->damageContextCause = null;
		$this->damageContextEntity = null;
		$this->damageContextTarget = null;
		$this->damageContextInventory = null;
		$this->damageContextSlot = null;
	}
}
