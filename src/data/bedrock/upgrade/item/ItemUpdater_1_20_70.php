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

final class ItemUpdater_1_20_70 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 70];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:grass" => "minecraft:grass_block",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:leaves" => [
				0 => "minecraft:oak_leaves",
				1 => "minecraft:spruce_leaves",
				2 => "minecraft:birch_leaves",
				3 => "minecraft:jungle_leaves",
			],
			"minecraft:leaves2" => [
				0 => "minecraft:acacia_leaves",
				1 => "minecraft:dark_oak_leaves",
			],
			"minecraft:spawn_egg" => [
				144 => "minecraft:bogged_spawn_egg",
			],
			"minecraft:wood" => [
				0 => "minecraft:oak_wood",
				1 => "minecraft:spruce_wood",
				2 => "minecraft:birch_wood",
				3 => "minecraft:jungle_wood",
				4 => "minecraft:acacia_wood",
				5 => "minecraft:dark_oak_wood",
				8 => "minecraft:stripped_oak_wood",
				9 => "minecraft:stripped_spruce_wood",
				10 => "minecraft:stripped_birch_wood",
				11 => "minecraft:stripped_jungle_wood",
				12 => "minecraft:stripped_acacia_wood",
				13 => "minecraft:stripped_dark_oak_wood",
			],
			"minecraft:wooden_slab" => [
				0 => "minecraft:oak_slab",
				1 => "minecraft:spruce_slab",
				2 => "minecraft:birch_slab",
				3 => "minecraft:jungle_slab",
				4 => "minecraft:acacia_slab",
				5 => "minecraft:dark_oak_slab",
			],
		];
	}
}
