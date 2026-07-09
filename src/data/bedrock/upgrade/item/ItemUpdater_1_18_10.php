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

final class ItemUpdater_1_18_10 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 18, 10];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:banner_pattern" => [
				7 => "minecraft:globe_banner_pattern",
			],
			"minecraft:bucket" => [
				13 => "minecraft:tadpole_bucket",
			],
			"minecraft:spawn_egg" => [
				132 => "minecraft:frog_spawn_egg",
				133 => "minecraft:tadpole_spawn_egg",
				134 => "minecraft:allay_spawn_egg",
				135 => "minecraft:firefly_spawn_egg",
			],
		];
	}
}
