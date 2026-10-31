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

/**
 * Outcome of combining, repairing or renaming items in an anvil.
 */
final class AnvilCraftResult{

	public function __construct(
		private readonly Item $input,
		private readonly Item $material,
		private readonly Item $output,
		private readonly int $xpCost,
		private readonly int $materialCost
	){}

	public function getInput() : Item{
		return clone $this->input;
	}

	public function getMaterial() : Item{
		return clone $this->material;
	}

	public function getOutput() : Item{
		return clone $this->output;
	}

	public function getXpCost() : int{
		return $this->xpCost;
	}

	/**
	 * Returns how many items of the material slot are consumed by the operation.
	 */
	public function getMaterialCost() : int{
		return $this->materialCost;
	}
}
