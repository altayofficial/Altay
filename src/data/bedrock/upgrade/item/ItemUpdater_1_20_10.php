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

final class ItemUpdater_1_20_10 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 10];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:concrete" => [
				0 => "minecraft:white_concrete",
				1 => "minecraft:orange_concrete",
				2 => "minecraft:magenta_concrete",
				3 => "minecraft:light_blue_concrete",
				4 => "minecraft:yellow_concrete",
				5 => "minecraft:lime_concrete",
				6 => "minecraft:pink_concrete",
				7 => "minecraft:gray_concrete",
				8 => "minecraft:light_gray_concrete",
				9 => "minecraft:cyan_concrete",
				10 => "minecraft:purple_concrete",
				11 => "minecraft:blue_concrete",
				12 => "minecraft:brown_concrete",
				13 => "minecraft:green_concrete",
				14 => "minecraft:red_concrete",
				15 => "minecraft:black_concrete",
			],
			"minecraft:shulker_box" => [
				0 => "minecraft:white_shulker_box",
				1 => "minecraft:orange_shulker_box",
				2 => "minecraft:magenta_shulker_box",
				3 => "minecraft:light_blue_shulker_box",
				4 => "minecraft:yellow_shulker_box",
				5 => "minecraft:lime_shulker_box",
				6 => "minecraft:pink_shulker_box",
				7 => "minecraft:gray_shulker_box",
				8 => "minecraft:light_gray_shulker_box",
				9 => "minecraft:cyan_shulker_box",
				10 => "minecraft:purple_shulker_box",
				11 => "minecraft:blue_shulker_box",
				12 => "minecraft:brown_shulker_box",
				13 => "minecraft:green_shulker_box",
				14 => "minecraft:red_shulker_box",
				15 => "minecraft:black_shulker_box",
			],
		];
	}
}
