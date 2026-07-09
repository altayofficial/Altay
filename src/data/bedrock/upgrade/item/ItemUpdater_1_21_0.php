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

final class ItemUpdater_1_21_0 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 21, 0];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:record_creator" => "minecraft:music_disc_creator",
			"minecraft:record_creator_music_box" => "minecraft:music_disc_creator_music_box",
			"minecraft:record_precipice" => "minecraft:music_disc_precipice",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:coral_block" => [
				0 => "minecraft:tube_coral_block",
				1 => "minecraft:brain_coral_block",
				2 => "minecraft:bubble_coral_block",
				3 => "minecraft:fire_coral_block",
				4 => "minecraft:horn_coral_block",
				8 => "minecraft:dead_tube_coral_block",
				9 => "minecraft:dead_brain_coral_block",
				10 => "minecraft:dead_bubble_coral_block",
				11 => "minecraft:dead_fire_coral_block",
				12 => "minecraft:dead_horn_coral_block",
			],
			"minecraft:double_plant" => [
				0 => "minecraft:sunflower",
				1 => "minecraft:lilac",
				2 => "minecraft:tall_grass",
				3 => "minecraft:large_fern",
				4 => "minecraft:rose_bush",
				5 => "minecraft:peony",
			],
			"minecraft:stone_block_slab" => [
				0 => "minecraft:smooth_stone_slab",
				1 => "minecraft:sandstone_slab",
				2 => "minecraft:petrified_oak_slab",
				3 => "minecraft:cobblestone_slab",
				4 => "minecraft:brick_slab",
				5 => "minecraft:stone_brick_slab",
				6 => "minecraft:quartz_slab",
				7 => "minecraft:nether_brick_slab",
			],
			"minecraft:tallgrass" => [
				0 => "minecraft:short_grass",
				2 => "minecraft:fern",
				3 => "minecraft:fern",
			],
		];
	}
}
