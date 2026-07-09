<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\upgrade\item;

use pocketmine\utils\SingletonTrait;

final class ItemUpdater_1_20_0 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 0];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:record_relic" => "minecraft:music_disc_relic",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:carpet" => [
				0 => "minecraft:white_carpet",
				1 => "minecraft:orange_carpet",
				2 => "minecraft:magenta_carpet",
				3 => "minecraft:light_blue_carpet",
				4 => "minecraft:yellow_carpet",
				5 => "minecraft:lime_carpet",
				6 => "minecraft:pink_carpet",
				7 => "minecraft:gray_carpet",
				8 => "minecraft:light_gray_carpet",
				9 => "minecraft:cyan_carpet",
				10 => "minecraft:purple_carpet",
				11 => "minecraft:blue_carpet",
				12 => "minecraft:brown_carpet",
				13 => "minecraft:green_carpet",
				14 => "minecraft:red_carpet",
				15 => "minecraft:black_carpet",
			],
			"minecraft:coral" => [
				0 => "minecraft:tube_coral",
				1 => "minecraft:brain_coral",
				2 => "minecraft:bubble_coral",
				3 => "minecraft:fire_coral",
				4 => "minecraft:horn_coral",
				8 => "minecraft:dead_tube_coral",
				9 => "minecraft:dead_brain_coral",
				10 => "minecraft:dead_bubble_coral",
				11 => "minecraft:dead_fire_coral",
				12 => "minecraft:dead_horn_coral",
			],
		];
	}
}
