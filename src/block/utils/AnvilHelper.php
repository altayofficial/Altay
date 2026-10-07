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

namespace pocketmine\block\utils;

use pocketmine\crafting\AnvilCraftResult;
use pocketmine\item\Durable;
use pocketmine\item\EnchantedBook;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\Item;
use function intdiv;
use function max;
use function mb_strlen;
use function mb_substr;
use function min;

final class AnvilHelper{
	public const MAX_NAME_LENGTH = 30;
	public const TOO_EXPENSIVE_COST = 40;

	private function __construct(){

	}

	/**
	 * Computes the result of putting the given items in an anvil, or null if the operation is not possible.
	 *
	 * @param string|null $name the name typed in the rename field, or null if the field was not used
	 */
	public static function calculateResult(Item $input, Item $material, ?string $name, bool $creative) : ?AnvilCraftResult{
		if($input->isNull()){
			return null;
		}

		$output = clone $input;
		$cost = 0;
		$materialCost = 0;

		if(!$material->isNull()){
			if($output instanceof Durable && $output->isValidRepairMaterial($material)){
				$materialCost = self::repairWithMaterial($output, $material, $cost);
				if($materialCost === 0){
					return null;
				}
			}else{
				$materialIsBook = $material instanceof EnchantedBook && $material->hasEnchantments();
				if(!$materialIsBook && (!$output instanceof Durable || $material->getTypeId() !== $input->getTypeId())){
					return null;
				}
				if(!$materialIsBook && $output instanceof Durable && $material instanceof Durable && $output->getDamage() > 0){
					self::combineDurability($output, $material);
					$cost += 2;
				}
				if($material->hasEnchantments() && !self::combineEnchantments($output, $material, $materialIsBook, $creative, $cost)){
					return null;
				}
				$materialCost = 1;
			}
		}

		$renamed = self::applyName($output, $name, $cost);
		if($cost <= 0){
			return null;
		}

		$renameOnly = $materialCost === 0 && $renamed;
		$total = $input->getRepairCost() + ($materialCost > 0 ? $material->getRepairCost() : 0) + $cost;
		if($input->getCount() > 1 && $materialCost > 0 && $material->hasEnchantments()){
			$total = max($total, self::TOO_EXPENSIVE_COST);
		}
		if($renameOnly && $total >= self::TOO_EXPENSIVE_COST){
			$total = self::TOO_EXPENSIVE_COST - 1;
		}
		if($total >= self::TOO_EXPENSIVE_COST && !$creative){
			return null;
		}

		if(!$renameOnly){
			$output->setRepairCost(max($input->getRepairCost(), $material->getRepairCost()) * 2 + 1);
		}

		$consumedMaterial = clone $material;
		$consumedMaterial->setCount($materialCost);

		return new AnvilCraftResult($input, $consumedMaterial, $output, $total, $materialCost);
	}

	/**
	 * Truncates a name typed by a player to the length accepted by the anvil.
	 */
	public static function sanitizeName(string $name) : string{
		if(mb_strlen($name, "UTF-8") > self::MAX_NAME_LENGTH){
			return mb_substr($name, 0, self::MAX_NAME_LENGTH, "UTF-8");
		}

		return $name;
	}

	private static function repairWithMaterial(Durable $output, Item $material, int &$cost) : int{
		$available = $material->getCount();
		$repairPerUnit = max(1, $output->getRepairAmount($material));
		$used = 0;
		while($output->getDamage() > 0 && $used < $available){
			$output->setDamage($output->getDamage() - min($output->getDamage(), $repairPerUnit));
			$cost++;
			$used++;
		}

		return $used;
	}

	private static function combineDurability(Durable $output, Durable $material) : void{
		$maxDurability = $output->getMaxDurability();
		$remaining = ($maxDurability - $output->getDamage()) + $output->getCombineRepairAmount($material);
		$output->setDamage(max(0, $maxDurability - $remaining));
	}

	private static function combineEnchantments(Item $output, Item $material, bool $materialIsBook, bool $creative, int &$cost) : bool{
		$registry = AvailableEnchantmentRegistry::getInstance();
		$acceptsAny = $output instanceof EnchantedBook || $creative;
		$applied = false;

		foreach($material->getEnchantments() as $instance){
			$type = $instance->getType();
			if((!$acceptsAny && !$registry->isAvailableForItem($type, $output)) || !self::isCompatibleWithAll($type, $output)){
				$cost++;
				continue;
			}

			$currentLevel = $output->getEnchantmentLevel($type);
			$level = $instance->getLevel();
			$newLevel = $currentLevel === $level ? $level + 1 : max($currentLevel, $level);
			$newLevel = min($newLevel, $type->getMaxLevel());
			if($newLevel < $currentLevel){
				$newLevel = $currentLevel;
			}

			$cost += self::getRarityMultiplier($type, $materialIsBook) * ($newLevel - $currentLevel);
			if($newLevel !== $currentLevel){
				$output->addEnchantment(new EnchantmentInstance($type, $newLevel));
			}
			$applied = true;
		}

		return $applied;
	}

	private static function isCompatibleWithAll(Enchantment $type, Item $output) : bool{
		foreach($output->getEnchantments() as $existing){
			$existingType = $existing->getType();
			if($existingType !== $type && !$existingType->isCompatibleWith($type)){
				return false;
			}
		}

		return true;
	}

	private static function getRarityMultiplier(Enchantment $type, bool $fromBook) : int{
		$multiplier = match($type->getRarity()){
			Rarity::COMMON => 1,
			Rarity::UNCOMMON => 2,
			Rarity::RARE => 4,
			default => 8
		};

		return $fromBook ? max(1, intdiv($multiplier, 2)) : $multiplier;
	}

	private static function applyName(Item $output, ?string $name, int &$cost) : bool{
		if($name === null){
			return false;
		}

		if($name === ""){
			if(!$output->hasCustomName()){
				return false;
			}
			$output->clearCustomName();
			$cost++;
			return true;
		}

		if($name === $output->getCustomName() || (!$output->hasCustomName() && $name === $output->getName())){
			return false;
		}

		$output->setCustomName($name);
		$cost++;
		return true;
	}
}
