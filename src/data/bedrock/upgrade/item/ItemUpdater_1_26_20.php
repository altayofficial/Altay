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

final class ItemUpdater_1_26_20 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 26, 20];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:bucket" => [
				14 => "minecraft:sulfur_cube_bucket",
			],
			"minecraft:spawn_egg" => [
				149 => "minecraft:nautilus_spawn_egg",
				150 => "minecraft:zombie_nautilus_spawn_egg",
				151 => "minecraft:parched_spawn_egg",
				152 => "minecraft:camel_husk_spawn_egg",
				153 => "minecraft:sulfur_cube_spawn_egg",
			],
		];
	}
}
