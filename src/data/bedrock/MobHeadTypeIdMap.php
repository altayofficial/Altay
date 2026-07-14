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

use pocketmine\block\utils\MobHeadType;
use pocketmine\utils\SingletonTrait;

final class MobHeadTypeIdMap{
	use SingletonTrait;
	/** @phpstan-use IntSaveIdMapTrait<MobHeadType> */
	use IntSaveIdMapTrait;

	private function __construct(){
		foreach(MobHeadType::cases() as $case){
			$this->register(match($case){
				MobHeadType::SKELETON => 0,
				MobHeadType::WITHER_SKELETON => 1,
				MobHeadType::ZOMBIE => 2,
				MobHeadType::PLAYER => 3,
				MobHeadType::CREEPER => 4,
				MobHeadType::DRAGON => 5,
				MobHeadType::PIGLIN => 6,
			}, $case);
		}
	}
}
