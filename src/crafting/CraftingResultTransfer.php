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
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use function count;

/**
 * Helper for recipes whose outputs must inherit container NBT from inputs (e.g. dyeing shulkers).
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
}
