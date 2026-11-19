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

namespace pocketmine\block\utils;

enum CopperOxidation : int{
	case NONE = 0;
	case EXPOSED = 1;
	case WEATHERED = 2;
	case OXIDIZED = 3;

	public function getPrevious() : ?self{
		return self::tryFrom($this->value - 1);
	}

	public function getNext() : ?self{
		return self::tryFrom($this->value + 1);
	}
}
