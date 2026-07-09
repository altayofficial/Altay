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

final class ItemUpdater_1_20_30 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 30];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:concrete_powder" => [
				0 => "minecraft:white_concrete_powder",
				1 => "minecraft:orange_concrete_powder",
				2 => "minecraft:magenta_concrete_powder",
				3 => "minecraft:light_blue_concrete_powder",
				4 => "minecraft:yellow_concrete_powder",
				5 => "minecraft:lime_concrete_powder",
				6 => "minecraft:pink_concrete_powder",
				7 => "minecraft:gray_concrete_powder",
				8 => "minecraft:light_gray_concrete_powder",
				9 => "minecraft:cyan_concrete_powder",
				10 => "minecraft:purple_concrete_powder",
				11 => "minecraft:blue_concrete_powder",
				12 => "minecraft:brown_concrete_powder",
				13 => "minecraft:green_concrete_powder",
				14 => "minecraft:red_concrete_powder",
				15 => "minecraft:black_concrete_powder",
			],
			"minecraft:stained_hardened_clay" => [
				0 => "minecraft:white_terracotta",
				1 => "minecraft:orange_terracotta",
				2 => "minecraft:magenta_terracotta",
				3 => "minecraft:light_blue_terracotta",
				4 => "minecraft:yellow_terracotta",
				5 => "minecraft:lime_terracotta",
				6 => "minecraft:pink_terracotta",
				7 => "minecraft:gray_terracotta",
				8 => "minecraft:light_gray_terracotta",
				9 => "minecraft:cyan_terracotta",
				10 => "minecraft:purple_terracotta",
				11 => "minecraft:blue_terracotta",
				12 => "minecraft:brown_terracotta",
				13 => "minecraft:green_terracotta",
				14 => "minecraft:red_terracotta",
				15 => "minecraft:black_terracotta",
			],
		];
	}
}
