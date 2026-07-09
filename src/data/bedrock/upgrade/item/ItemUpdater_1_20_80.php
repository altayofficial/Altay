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

final class ItemUpdater_1_20_80 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 80];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:coral_fan" => [
				0 => "minecraft:tube_coral_fan",
				1 => "minecraft:brain_coral_fan",
				2 => "minecraft:bubble_coral_fan",
				3 => "minecraft:fire_coral_fan",
				4 => "minecraft:horn_coral_fan",
			],
			"minecraft:coral_fan_dead" => [
				0 => "minecraft:dead_tube_coral_fan",
				1 => "minecraft:dead_brain_coral_fan",
				2 => "minecraft:dead_bubble_coral_fan",
				3 => "minecraft:dead_fire_coral_fan",
				4 => "minecraft:dead_horn_coral_fan",
			],
			"minecraft:red_flower" => [
				0 => "minecraft:poppy",
				1 => "minecraft:blue_orchid",
				2 => "minecraft:allium",
				3 => "minecraft:azure_bluet",
				4 => "minecraft:red_tulip",
				5 => "minecraft:orange_tulip",
				6 => "minecraft:white_tulip",
				7 => "minecraft:pink_tulip",
				8 => "minecraft:oxeye_daisy",
				9 => "minecraft:cornflower",
				10 => "minecraft:lily_of_the_valley",
			],
			"minecraft:sapling" => [
				0 => "minecraft:oak_sapling",
				1 => "minecraft:spruce_sapling",
				2 => "minecraft:birch_sapling",
				3 => "minecraft:jungle_sapling",
				4 => "minecraft:acacia_sapling",
				5 => "minecraft:dark_oak_sapling",
			],
		];
	}
}
