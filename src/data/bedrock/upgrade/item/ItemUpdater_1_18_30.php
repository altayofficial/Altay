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

final class ItemUpdater_1_18_30 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 18, 30];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:concretepowder" => "minecraft:concrete_powder",
			"minecraft:invisiblebedrock" => "minecraft:invisible_bedrock",
			"minecraft:movingblock" => "minecraft:moving_block",
			"minecraft:pistonarmcollision" => "minecraft:piston_arm_collision",
			"minecraft:sealantern" => "minecraft:sea_lantern",
			"minecraft:stickypistonarmcollision" => "minecraft:sticky_piston_arm_collision",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:chest_boat" => [
				0 => "minecraft:oak_chest_boat",
				1 => "minecraft:spruce_chest_boat",
				2 => "minecraft:birch_chest_boat",
				3 => "minecraft:jungle_chest_boat",
				4 => "minecraft:acacia_chest_boat",
				5 => "minecraft:dark_oak_chest_boat",
			],
			"minecraft:spawn_egg" => [
				131 => "minecraft:warden_spawn_egg",
			],
		];
	}
}
