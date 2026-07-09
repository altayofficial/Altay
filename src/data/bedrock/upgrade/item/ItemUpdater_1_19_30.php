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

final class ItemUpdater_1_19_30 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 19, 30];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:double_stone_slab" => "minecraft:stone_block_slab",
			"minecraft:double_stone_slab2" => "minecraft:stone_block_slab2",
			"minecraft:double_stone_slab3" => "minecraft:stone_block_slab3",
			"minecraft:double_stone_slab4" => "minecraft:stone_block_slab4",
			"minecraft:real_double_stone_slab" => "minecraft:double_stone_block_slab",
			"minecraft:real_double_stone_slab2" => "minecraft:double_stone_block_slab2",
			"minecraft:real_double_stone_slab3" => "minecraft:double_stone_block_slab3",
			"minecraft:real_double_stone_slab4" => "minecraft:double_stone_block_slab4",
			"minecraft:record_5" => "minecraft:music_disc_5",
			"minecraft:stone_slab" => "minecraft:stone_block_slab",
			"minecraft:stone_slab2" => "minecraft:stone_block_slab2",
			"minecraft:stone_slab3" => "minecraft:stone_block_slab3",
			"minecraft:stone_slab4" => "minecraft:stone_block_slab4",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:boat" => [
				6 => "minecraft:mangrove_boat",
			],
			"minecraft:chest_boat" => [
				6 => "minecraft:mangrove_chest_boat",
			],
		];
	}
}
