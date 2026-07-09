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

final class ItemUpdater_1_19_70 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 19, 70];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:boat" => [
				7 => "minecraft:bamboo_raft",
			],
			"minecraft:chest_boat" => [
				7 => "minecraft:bamboo_chest_raft",
			],
			"minecraft:spawn_egg" => [
				20 => "minecraft:iron_golem_spawn_egg",
				21 => "minecraft:snow_golem_spawn_egg",
				52 => "minecraft:wither_spawn_egg",
				53 => "minecraft:ender_dragon_spawn_egg",
				138 => "minecraft:camel_spawn_egg",
				139 => "minecraft:sniffer_spawn_egg",
				157 => "minecraft:trader_llama_spawn_egg",
			],
			"minecraft:wool" => [
				0 => "minecraft:white_wool",
				1 => "minecraft:orange_wool",
				2 => "minecraft:magenta_wool",
				3 => "minecraft:light_blue_wool",
				4 => "minecraft:yellow_wool",
				5 => "minecraft:lime_wool",
				6 => "minecraft:pink_wool",
				7 => "minecraft:gray_wool",
				8 => "minecraft:light_gray_wool",
				9 => "minecraft:cyan_wool",
				10 => "minecraft:purple_wool",
				11 => "minecraft:blue_wool",
				12 => "minecraft:brown_wool",
				13 => "minecraft:green_wool",
				14 => "minecraft:red_wool",
				15 => "minecraft:black_wool",
			],
		];
	}
}
