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
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\crafting\json;

final class PotionContainerChangeRecipeData{
	/** @required */
	public string $input_item_name;

	/** @required */
	public RecipeIngredientData $ingredient;

	/** @required */
	public string $output_item_name;

	public function __construct(string $input_item_name, RecipeIngredientData $ingredient, string $output_item_name){
		$this->input_item_name = $input_item_name;
		$this->ingredient = $ingredient;
		$this->output_item_name = $output_item_name;
	}
}
