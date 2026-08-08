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

/**
 * Copies NBT from container crafting inputs onto recipe results.
 * This is needed for things like dyeing shulker boxes, which store their inventory
 * in the item's root named tag (Items), not in BlockEntityTag.
 */
final class CraftingResultTransfer{

	/**
	 * If an input has an Items tag, copies that input's named tag onto all results.
	 *
	 * @param Item[] $inputs
	 * @param Item[] $results
	 * @phpstan-param list<Item> $inputs
	 * @phpstan-param list<Item> $results
	 */
	public static function transferContainerNamedTag(array $inputs, array $results) : void{
		foreach($inputs as $input){
			if($input->getNamedTag()->getTag(Container::TAG_ITEMS) === null){
				continue;
			}
			$tag = clone $input->getNamedTag();
			foreach($results as $result){
				$result->setNamedTag(clone $tag);
			}
			return;
		}
	}
}
