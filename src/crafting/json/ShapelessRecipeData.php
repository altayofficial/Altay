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

use function count;

final class ShapelessRecipeData implements \JsonSerializable{

	/**
	 * @required
	 * @var RecipeIngredientData[]
	 * @phpstan-var list<RecipeIngredientData>
	 */
	public array $input;

	/**
	 * @required
	 * @var ItemStackData[]
	 * @phpstan-var list<ItemStackData>
	 */
	public array $output;

	/** @required */
	public string $block;

	/** @required */
	public int $priority;

	/** @var RecipeIngredientData[] */
	public array $unlockingIngredients = [];

	/**
	 * @param RecipeIngredientData[] $input
	 * @param ItemStackData[]        $output
	 * @param RecipeIngredientData[] $unlockingIngredients
	 *
	 * @phpstan-param list<RecipeIngredientData> $input
	 * @phpstan-param list<ItemStackData> $output
	 * @phpstan-param list<RecipeIngredientData> $unlockingIngredients
	 */
	public function __construct(array $input, array $output, string $block, int $priority, array $unlockingIngredients = []){
		$this->block = $block;
		$this->priority = $priority;
		$this->input = $input;
		$this->output = $output;
		$this->unlockingIngredients = $unlockingIngredients;
	}

	/**
	 * @return mixed[]
	 */
	public function jsonSerialize() : array{
		$result = (array) $this;
		if(count($this->unlockingIngredients) === 0){
			unset($result["unlockingIngredients"]);
		}
		return $result;
	}
}
