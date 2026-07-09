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

final class ItemUpdater_1_12_0 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 12, 0];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:glazedterracotta.black" => "minecraft:black_glazed_terracotta",
			"minecraft:glazedterracotta.blue" => "minecraft:blue_glazed_terracotta",
			"minecraft:glazedterracotta.brown" => "minecraft:brown_glazed_terracotta",
			"minecraft:glazedterracotta.cyan" => "minecraft:cyan_glazed_terracotta",
			"minecraft:glazedterracotta.gray" => "minecraft:gray_glazed_terracotta",
			"minecraft:glazedterracotta.green" => "minecraft:green_glazed_terracotta",
			"minecraft:glazedterracotta.light_blue" => "minecraft:light_blue_glazed_terracotta",
			"minecraft:glazedterracotta.lime" => "minecraft:lime_glazed_terracotta",
			"minecraft:glazedterracotta.magenta" => "minecraft:magenta_glazed_terracotta",
			"minecraft:glazedterracotta.orange" => "minecraft:orange_glazed_terracotta",
			"minecraft:glazedterracotta.pink" => "minecraft:pink_glazed_terracotta",
			"minecraft:glazedterracotta.purple" => "minecraft:purple_glazed_terracotta",
			"minecraft:glazedterracotta.red" => "minecraft:red_glazed_terracotta",
			"minecraft:glazedterracotta.silver" => "minecraft:silver_glazed_terracotta",
			"minecraft:glazedterracotta.white" => "minecraft:white_glazed_terracotta",
			"minecraft:glazedterracotta.yellow" => "minecraft:yellow_glazed_terracotta",
		];
	}
}
