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

final class ItemUpdater_1_21_30 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 21, 30];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:chemistry_table" => [
				0 => "minecraft:compound_creator",
				4 => "minecraft:material_reducer",
				5 => "minecraft:material_reducer",
				6 => "minecraft:material_reducer",
				7 => "minecraft:material_reducer",
				8 => "minecraft:element_constructor",
				9 => "minecraft:element_constructor",
				10 => "minecraft:element_constructor",
				11 => "minecraft:element_constructor",
				12 => "minecraft:lab_table",
				13 => "minecraft:lab_table",
				14 => "minecraft:lab_table",
				15 => "minecraft:lab_table",
			],
			"minecraft:cobblestone_wall" => [
				1 => "minecraft:mossy_cobblestone_wall",
				2 => "minecraft:granite_wall",
				3 => "minecraft:diorite_wall",
				4 => "minecraft:andesite_wall",
				5 => "minecraft:sandstone_wall",
				6 => "minecraft:brick_wall",
				7 => "minecraft:stone_brick_wall",
				8 => "minecraft:mossy_stone_brick_wall",
				9 => "minecraft:nether_brick_wall",
				10 => "minecraft:end_stone_brick_wall",
				11 => "minecraft:prismarine_wall",
				12 => "minecraft:red_sandstone_wall",
				13 => "minecraft:red_nether_brick_wall",
			],
			"minecraft:colored_torch_bp" => [
				0 => "minecraft:colored_torch_blue",
				8 => "minecraft:colored_torch_purple",
				9 => "minecraft:colored_torch_purple",
				10 => "minecraft:colored_torch_purple",
				11 => "minecraft:colored_torch_purple",
				12 => "minecraft:colored_torch_purple",
				13 => "minecraft:colored_torch_purple",
				14 => "minecraft:colored_torch_purple",
				15 => "minecraft:colored_torch_purple",
			],
			"minecraft:colored_torch_rg" => [
				0 => "minecraft:colored_torch_red",
				8 => "minecraft:colored_torch_green",
				9 => "minecraft:colored_torch_green",
				10 => "minecraft:colored_torch_green",
				11 => "minecraft:colored_torch_green",
				12 => "minecraft:colored_torch_green",
				13 => "minecraft:colored_torch_green",
				14 => "minecraft:colored_torch_green",
				15 => "minecraft:colored_torch_green",
			],
			"minecraft:purpur_block" => [
				1 => "minecraft:deprecated_purpur_block_1",
				2 => "minecraft:purpur_pillar",
				3 => "minecraft:deprecated_purpur_block_2",
			],
			"minecraft:sponge" => [
				1 => "minecraft:wet_sponge",
			],
			"minecraft:tnt" => [
				2 => "minecraft:underwater_tnt",
				3 => "minecraft:underwater_tnt",
			],
		];
	}
}
