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
use pocketmine\utils\ObjectSet;

final class FurnaceRecipeManager{
	/** @var FurnaceRecipe[] */
	protected array $furnaceRecipes = [];

	/**
	 * @var FurnaceRecipe[]
	 * @phpstan-var array<int, FurnaceRecipe>
	 */
	private array $lookupCache = [];

	/** @phpstan-var ObjectSet<\Closure(FurnaceRecipe) : void> */
	private ObjectSet $recipeRegisteredCallbacks;

	public function __construct(){
		$this->recipeRegisteredCallbacks = new ObjectSet();
	}

	/**
	 * @phpstan-return ObjectSet<\Closure(FurnaceRecipe) : void>
	 */
	public function getRecipeRegisteredCallbacks() : ObjectSet{
		return $this->recipeRegisteredCallbacks;
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getAll() : array{
		return $this->furnaceRecipes;
	}

	public function register(FurnaceRecipe $recipe) : void{
		$this->furnaceRecipes[] = $recipe;
		foreach($this->recipeRegisteredCallbacks as $callback){
			$callback($recipe);
		}
	}

	public function match(Item $input) : ?FurnaceRecipe{
		$index = $input->getStateId();
		$simpleRecipe = $this->lookupCache[$index] ?? null;
		if($simpleRecipe !== null){
			return $simpleRecipe;
		}

		foreach($this->furnaceRecipes as $recipe){
			if($recipe->getInput()->accepts($input)){
				//remember that this item is accepted by this recipe, so we don't need to bruteforce it again
				$this->lookupCache[$index] = $recipe;
				return $recipe;
			}
		}

		return null;
	}
}
