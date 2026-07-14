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

use pocketmine\item\FireworkRocketType;
use pocketmine\utils\SingletonTrait;

final class FireworkRocketTypeIdMap{
	use SingletonTrait;
	/** @phpstan-use IntSaveIdMapTrait<FireworkRocketType> */
	use IntSaveIdMapTrait;

	private function __construct(){
		foreach(FireworkRocketType::cases() as $case){
			$this->register(match($case){
				FireworkRocketType::SMALL_BALL => FireworkRocketTypeIds::SMALL_BALL,
				FireworkRocketType::LARGE_BALL => FireworkRocketTypeIds::LARGE_BALL,
				FireworkRocketType::STAR => FireworkRocketTypeIds::STAR,
				FireworkRocketType::CREEPER => FireworkRocketTypeIds::CREEPER,
				FireworkRocketType::BURST => FireworkRocketTypeIds::BURST,
			}, $case);
		}
	}
}
