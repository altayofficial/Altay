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

namespace pocketmine\item;

use pocketmine\block\utils\DyeColor;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Dye extends Item{
	private DyeColor $color = DyeColor::BLACK;

	protected function describeState(RuntimeDataDescriber $w) : void{
		$w->enum($this->color);
	}

	public function getColor() : DyeColor{
		return $this->color;
	}

	/**
	 * @return $this
	 */
	public function setColor(DyeColor $color) : self{
		$this->color = $color;
		return $this;
	}
}
