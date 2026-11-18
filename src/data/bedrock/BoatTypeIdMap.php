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

namespace pocketmine\data\bedrock;

use pocketmine\item\BoatType;
use pocketmine\utils\SingletonTrait;

/**
 * Maps boat types to the variant a boat entity is saved and sent with. Bamboo sits at 7 because rafts were
 * added before cherry, so this doesn't follow the declaration order of BoatType.
 */
final class BoatTypeIdMap{
	use SingletonTrait;
	/** @phpstan-use IntSaveIdMapTrait<BoatType> */
	use IntSaveIdMapTrait;

	private function __construct(){
		foreach(BoatType::cases() as $case){
			$this->register(match($case){
				BoatType::OAK => BoatTypeIds::OAK,
				BoatType::SPRUCE => BoatTypeIds::SPRUCE,
				BoatType::BIRCH => BoatTypeIds::BIRCH,
				BoatType::JUNGLE => BoatTypeIds::JUNGLE,
				BoatType::ACACIA => BoatTypeIds::ACACIA,
				BoatType::DARK_OAK => BoatTypeIds::DARK_OAK,
				BoatType::MANGROVE => BoatTypeIds::MANGROVE,
				BoatType::BAMBOO => BoatTypeIds::BAMBOO,
				BoatType::CHERRY => BoatTypeIds::CHERRY,
				BoatType::PALE_OAK => BoatTypeIds::PALE_OAK,
				BoatType::POPLAR => BoatTypeIds::POPLAR,
			}, $case);
		}
	}
}
