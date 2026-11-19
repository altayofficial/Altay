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

use pocketmine\world\generator\object\TreeType;

enum SaplingType{
	case OAK;
	case SPRUCE;
	case BIRCH;
	case JUNGLE;
	case ACACIA;
	case DARK_OAK;
	case CHERRY;

	public function getTreeType() : TreeType{
		return match($this){
			self::OAK => TreeType::OAK,
			self::SPRUCE => TreeType::SPRUCE,
			self::BIRCH => TreeType::BIRCH,
			self::JUNGLE => TreeType::JUNGLE,
			self::ACACIA => TreeType::ACACIA,
			self::DARK_OAK => TreeType::DARK_OAK,
			self::CHERRY => TreeType::CHERRY,
		};
	}

	public function getDisplayName() : string{
		return $this->getTreeType()->getDisplayName();
	}
}
