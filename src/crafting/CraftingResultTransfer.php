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

namespace pocketmine\crafting;

use pocketmine\block\tile\Container;
use pocketmine\item\Bundle;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use function count;

/**
 * Helper for recipes whose outputs must inherit container NBT from inputs (e.g. dyeing shulkers or bundles).
 * Shulker contents live under the root Items tag; we also mirror them into BlockEntityTag.
 */
final class CraftingResultTransfer{

	/**
	 * Merges Items/Lock (and custom name/lore) from the first matching input onto all results.
	 * Only the container tags are touched — existing result NBT is otherwise preserved.
	 *
	 * @param Item[] $inputs
	 * @param Item[] $results
	 * @phpstan-param array<int, Item> $inputs
	 * @phpstan-param array<int, Item> $results
	 */
	public static function transferContainerNamedTag(array $inputs, array $results) : void{
		self::transferBundleContents($inputs, $results);

		foreach($inputs as $input){
			$inputTag = $input->getNamedTag();
			$itemsTag = $inputTag->getTag(Container::TAG_ITEMS);
			if($itemsTag === null){
				continue;
			}

			$lockTag = $inputTag->getTag(Container::TAG_LOCK);
			foreach($results as $result){
				$resultTag = $result->getNamedTag();
				$resultTag->setTag(Container::TAG_ITEMS, clone $itemsTag);

				$blockEntityTag = $result->getCustomBlockData() ?? new CompoundTag();
				$blockEntityTag->setTag(Container::TAG_ITEMS, clone $itemsTag);

				if($lockTag !== null){
					$resultTag->setTag(Container::TAG_LOCK, clone $lockTag);
					$blockEntityTag->setTag(Container::TAG_LOCK, clone $lockTag);
				}else{
					$resultTag->removeTag(Container::TAG_LOCK);
					$blockEntityTag->removeTag(Container::TAG_LOCK);
				}
				$result->setCustomBlockData($blockEntityTag);

				if($input->hasCustomName()){
					$result->setCustomName($input->getCustomName());
				}
				$lore = $input->getLore();
				if(count($lore) > 0){
					$result->setLore($lore);
				}
			}
			return;
		}
	}

	/**
	 * Dyeing a bundle gives back a new one, so whatever was inside has to be carried over or it would vanish.
	 *
	 * @param Item[] $inputs
	 * @param Item[] $results
	 * @phpstan-param array<int, Item> $inputs
	 * @phpstan-param array<int, Item> $results
	 */
	private static function transferBundleContents(array $inputs, array $results) : void{
		foreach($inputs as $input){
			if(!$input instanceof Bundle || $input->isEmpty()){
				continue;
			}
			foreach($results as $result){
				if($result instanceof Bundle){
					$result->setContents($input->getContents());
				}
			}
			return;
		}
	}

	/**
	 * Smithing transforms (e.g. netherite upgrades) keep everything from the base item (enchantments, custom name,
	 * damage, trim...) and only change its type. The base is the input accepted by the recipe's first ingredient.
	 *
	 * @param Item[] $inputs
	 * @param Item[] $results
	 * @phpstan-param array<int, Item> $inputs
	 * @phpstan-param array<int, Item> $results
	 */
	public static function transferSmithingBaseNamedTag(ShapelessRecipe $recipe, array $inputs, array $results) : void{
		if($recipe->getType() !== ShapelessRecipeType::SMITHING){
			return;
		}
		$baseIngredient = $recipe->getIngredientList()[0] ?? null;
		if($baseIngredient === null){
			return;
		}
		foreach($inputs as $input){
			if(!$baseIngredient->accepts($input)){
				continue;
			}
			foreach($results as $result){
				$result->setNamedTag(clone $input->getNamedTag());
			}
			return;
		}
	}
}
