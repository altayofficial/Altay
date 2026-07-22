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

use pocketmine\data\bedrock\ItemTagToIdMap;
use pocketmine\item\Item;
use pocketmine\world\format\io\GlobalItemDataHandlers;

/**
 * Recipe ingredient that matches items whose ID falls within a specific set. This is used for magic meta value
 * wildcards and also for ingredients which use item tags (since tags implicitly rely on ID only).
 *
 * @internal
 */
final class TagWildcardRecipeIngredient implements RecipeIngredient{

	public function __construct(
		private string $tagName
	){}

	public function getTagName() : string{ return $this->tagName; }

	public function accepts(Item $item) : bool{
		if($item->getCount() < 1){
			return false;
		}

		return ItemTagToIdMap::getInstance()->tagContainsId($this->tagName, GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName());
	}

	public function __toString() : string{
		return "TagWildcardRecipeIngredient($this->tagName)";
	}
}
