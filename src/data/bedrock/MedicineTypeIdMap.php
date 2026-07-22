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

use pocketmine\item\MedicineType;
use pocketmine\utils\SingletonTrait;

final class MedicineTypeIdMap{
	use SingletonTrait;
	/** @phpstan-use IntSaveIdMapTrait<MedicineType> */
	use IntSaveIdMapTrait;

	private function __construct(){
		foreach(MedicineType::cases() as $case){
			$this->register(match($case){
				MedicineType::ANTIDOTE => MedicineTypeIds::ANTIDOTE,
				MedicineType::ELIXIR => MedicineTypeIds::ELIXIR,
				MedicineType::EYE_DROPS => MedicineTypeIds::EYE_DROPS,
				MedicineType::TONIC => MedicineTypeIds::TONIC,
			}, $case);
		}
	}
}
