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

final class ItemUpdater_1_21_20 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 21, 20];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:yellow_flower" => "minecraft:dandelion",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:anvil" => [
				4 => "minecraft:chipped_anvil",
				5 => "minecraft:chipped_anvil",
				6 => "minecraft:chipped_anvil",
				7 => "minecraft:chipped_anvil",
				8 => "minecraft:damaged_anvil",
				9 => "minecraft:damaged_anvil",
				10 => "minecraft:damaged_anvil",
				11 => "minecraft:damaged_anvil",
			],
			"minecraft:dirt" => [
				1 => "minecraft:coarse_dirt",
			],
			"minecraft:double_stone_block_slab" => [
				0 => "minecraft:smooth_stone_double_slab",
				1 => "minecraft:sandstone_double_slab",
				2 => "minecraft:petrified_oak_double_slab",
				3 => "minecraft:cobblestone_double_slab",
				4 => "minecraft:brick_double_slab",
				5 => "minecraft:stone_brick_double_slab",
				6 => "minecraft:quartz_double_slab",
				7 => "minecraft:nether_brick_double_slab",
			],
			"minecraft:double_stone_block_slab2" => [
				0 => "minecraft:red_sandstone_double_slab",
				1 => "minecraft:purpur_double_slab",
				2 => "minecraft:prismarine_double_slab",
				3 => "minecraft:dark_prismarine_double_slab",
				4 => "minecraft:prismarine_brick_double_slab",
				5 => "minecraft:mossy_cobblestone_double_slab",
				6 => "minecraft:smooth_sandstone_double_slab",
				7 => "minecraft:red_nether_brick_double_slab",
			],
			"minecraft:double_stone_block_slab3" => [
				0 => "minecraft:end_stone_brick_double_slab",
				1 => "minecraft:smooth_red_sandstone_double_slab",
				2 => "minecraft:polished_andesite_double_slab",
				3 => "minecraft:andesite_double_slab",
				4 => "minecraft:diorite_double_slab",
				5 => "minecraft:polished_diorite_double_slab",
				6 => "minecraft:granite_double_slab",
				7 => "minecraft:polished_granite_double_slab",
			],
			"minecraft:double_stone_block_slab4" => [
				0 => "minecraft:mossy_stone_brick_double_slab",
				1 => "minecraft:smooth_quartz_double_slab",
				2 => "minecraft:normal_stone_double_slab",
				3 => "minecraft:cut_sandstone_double_slab",
				4 => "minecraft:cut_red_sandstone_double_slab",
			],
			"minecraft:light_block" => [
				0 => "minecraft:light_block_0",
				1 => "minecraft:light_block_1",
				2 => "minecraft:light_block_2",
				3 => "minecraft:light_block_3",
				4 => "minecraft:light_block_4",
				5 => "minecraft:light_block_5",
				6 => "minecraft:light_block_6",
				7 => "minecraft:light_block_7",
				8 => "minecraft:light_block_8",
				9 => "minecraft:light_block_9",
				10 => "minecraft:light_block_10",
				11 => "minecraft:light_block_11",
				12 => "minecraft:light_block_12",
				13 => "minecraft:light_block_13",
				14 => "minecraft:light_block_14",
				15 => "minecraft:light_block_15",
			],
			"minecraft:monster_egg" => [
				0 => "minecraft:infested_stone",
				1 => "minecraft:infested_cobblestone",
				2 => "minecraft:infested_stone_bricks",
				3 => "minecraft:infested_mossy_stone_bricks",
				4 => "minecraft:infested_cracked_stone_bricks",
				5 => "minecraft:infested_chiseled_stone_bricks",
			],
			"minecraft:prismarine" => [
				1 => "minecraft:dark_prismarine",
				2 => "minecraft:prismarine_bricks",
			],
			"minecraft:quartz_block" => [
				1 => "minecraft:chiseled_quartz_block",
				2 => "minecraft:quartz_pillar",
				3 => "minecraft:smooth_quartz",
			],
			"minecraft:red_sandstone" => [
				1 => "minecraft:chiseled_red_sandstone",
				2 => "minecraft:cut_red_sandstone",
				3 => "minecraft:smooth_red_sandstone",
			],
			"minecraft:sand" => [
				1 => "minecraft:red_sand",
			],
			"minecraft:sandstone" => [
				1 => "minecraft:chiseled_sandstone",
				2 => "minecraft:cut_sandstone",
				3 => "minecraft:smooth_sandstone",
			],
			"minecraft:stone_block_slab2" => [
				0 => "minecraft:red_sandstone_slab",
				1 => "minecraft:purpur_slab",
				2 => "minecraft:prismarine_slab",
				3 => "minecraft:dark_prismarine_slab",
				4 => "minecraft:prismarine_brick_slab",
				5 => "minecraft:mossy_cobblestone_slab",
				6 => "minecraft:smooth_sandstone_slab",
				7 => "minecraft:red_nether_brick_slab",
			],
			"minecraft:stone_block_slab3" => [
				0 => "minecraft:end_stone_brick_slab",
				1 => "minecraft:smooth_red_sandstone_slab",
				2 => "minecraft:polished_andesite_slab",
				3 => "minecraft:andesite_slab",
				4 => "minecraft:diorite_slab",
				5 => "minecraft:polished_diorite_slab",
				6 => "minecraft:granite_slab",
				7 => "minecraft:polished_granite_slab",
			],
			"minecraft:stone_block_slab4" => [
				0 => "minecraft:mossy_stone_brick_slab",
				1 => "minecraft:smooth_quartz_slab",
				2 => "minecraft:normal_stone_slab",
				3 => "minecraft:cut_sandstone_slab",
				4 => "minecraft:cut_red_sandstone_slab",
			],
			"minecraft:stonebrick" => [
				0 => "minecraft:stone_bricks",
				1 => "minecraft:mossy_stone_bricks",
				2 => "minecraft:cracked_stone_bricks",
				3 => "minecraft:chiseled_stone_bricks",
			],
		];
	}
}
