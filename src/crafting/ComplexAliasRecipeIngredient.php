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

use pocketmine\item\Item;
use function array_map;
use function count;
use function implode;

final class ComplexAliasRecipeIngredient implements RecipeIngredient{

	/** @var Item[] */
	private array $items;

	/**
	 * @param Item[] $items
	 */
	public function __construct(
		private string $aliasName,
		array $items
	){
		if(count($items) === 0){
			throw new \InvalidArgumentException("Complex alias ingredient must accept at least one item");
		}
		foreach($items as $item){
			if($item->isNull()){
				throw new \InvalidArgumentException("Recipe ingredients must not be air items");
			}
			if($item->getCount() !== 1){
				throw new \InvalidArgumentException("Recipe ingredients cannot require count");
			}
		}
		$this->items = array_map(fn(Item $item) => clone $item, $items);
	}

	public function getAliasName() : string{ return $this->aliasName; }

	/**
	 * @return Item[]
	 */
	public function getItems() : array{
		return array_map(fn(Item $item) => clone $item, $this->items);
	}

	public function accepts(Item $item) : bool{
		if($item->getCount() < 1){
			return false;
		}
		foreach($this->items as $candidate){
			if($candidate->equals($item, true, $candidate->hasNamedTag())){
				return true;
			}
		}
		return false;
	}

	public function __toString() : string{
		return "ComplexAliasRecipeIngredient($this->aliasName: " . implode(", ", array_map(fn(Item $item) => (string) $item, $this->items)) . ")";
	}
}
