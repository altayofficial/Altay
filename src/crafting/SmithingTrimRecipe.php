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

use pocketmine\utils\AssumptionFailedError;

/**
 * Represents the smithing table's armor trim recipe. This only exists so the client knows which items are valid in
 * each smithing table slot and to advertise the recipe in the recipe book - the actual result item (which depends on
 * the specific pattern and material used) is computed by {@link \pocketmine\inventory\transaction\SmithingTrimTransaction}
 * rather than through the generic recipe matching system, since getResultsFor()/matchesCraftingGrid() aren't called.
 */
final class SmithingTrimRecipe implements CraftingRecipe{

	public function __construct(
		private RecipeIngredient $base,
		private RecipeIngredient $addition,
		private RecipeIngredient $template
	){}

	public function getBase() : RecipeIngredient{
		return $this->base;
	}

	public function getAddition() : RecipeIngredient{
		return $this->addition;
	}

	public function getTemplate() : RecipeIngredient{
		return $this->template;
	}

	public function getIngredientList() : array{
		return [$this->base, $this->addition, $this->template];
	}

	public function getResultsFor(CraftingGrid $grid) : array{
		throw new AssumptionFailedError("Armor trim results are computed by SmithingTrimTransaction, not through the generic recipe system");
	}

	public function matchesCraftingGrid(CraftingGrid $grid) : bool{
		return false;
	}
}
