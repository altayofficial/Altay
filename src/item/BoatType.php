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

use pocketmine\block\utils\WoodType;

enum BoatType{
	case OAK;
	case SPRUCE;
	case BIRCH;
	case JUNGLE;
	case ACACIA;
	case DARK_OAK;
	case MANGROVE;
	case CHERRY;
	case PALE_OAK;
	case BAMBOO;
	case POPLAR;

	public function getWoodType() : WoodType{
		return match($this){
			self::OAK => WoodType::OAK,
			self::SPRUCE => WoodType::SPRUCE,
			self::BIRCH => WoodType::BIRCH,
			self::JUNGLE => WoodType::JUNGLE,
			self::ACACIA => WoodType::ACACIA,
			self::DARK_OAK => WoodType::DARK_OAK,
			self::MANGROVE => WoodType::MANGROVE,
			self::CHERRY => WoodType::CHERRY,
			self::PALE_OAK => WoodType::PALE_OAK,
			self::BAMBOO => WoodType::BAMBOO,
			self::POPLAR => WoodType::POPLAR,
		};
	}

	public function getVehicleName() : string{
		return $this === self::BAMBOO ? "Raft" : "Boat"; // lmao
	}

	public function getDisplayName() : string{
		return $this->getWoodType()->getDisplayName();
	}
}
