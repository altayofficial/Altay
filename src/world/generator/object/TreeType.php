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

namespace pocketmine\world\generator\object;


enum TreeType{
	case OAK;
	case SPRUCE;
	case BIRCH;
	case JUNGLE;
	case ACACIA;
	case DARK_OAK;
	case CRIMSON;
	case WARPED;
	case AZALEA;
	case CHERRY;
	//TODO: mangrove
	//TODO: perhaps huge mushrooms should be here too???

	public function getDisplayName() : string{
		return match($this){
			self::OAK => "Oak",
			self::SPRUCE => "Spruce",
			self::BIRCH => "Birch",
			self::JUNGLE => "Jungle",
			self::ACACIA => "Acacia",
			self::DARK_OAK => "Dark Oak",
			self::CRIMSON => "Crimson",
			self::WARPED => "Warped",
			self::AZALEA => "Azalea",
			self::CHERRY => "Cherry",
		};
	}
}
