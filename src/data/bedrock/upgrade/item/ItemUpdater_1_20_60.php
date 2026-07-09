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

final class ItemUpdater_1_20_60 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 60];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:scute" => "minecraft:turtle_scute",
		];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:hard_stained_glass" => [
				0 => "minecraft:hard_white_stained_glass",
				1 => "minecraft:hard_orange_stained_glass",
				2 => "minecraft:hard_magenta_stained_glass",
				3 => "minecraft:hard_light_blue_stained_glass",
				4 => "minecraft:hard_yellow_stained_glass",
				5 => "minecraft:hard_lime_stained_glass",
				6 => "minecraft:hard_pink_stained_glass",
				7 => "minecraft:hard_gray_stained_glass",
				8 => "minecraft:hard_light_gray_stained_glass",
				9 => "minecraft:hard_cyan_stained_glass",
				10 => "minecraft:hard_purple_stained_glass",
				11 => "minecraft:hard_blue_stained_glass",
				12 => "minecraft:hard_brown_stained_glass",
				13 => "minecraft:hard_green_stained_glass",
				14 => "minecraft:hard_red_stained_glass",
				15 => "minecraft:hard_black_stained_glass",
			],
			"minecraft:hard_stained_glass_pane" => [
				0 => "minecraft:hard_white_stained_glass_pane",
				1 => "minecraft:hard_orange_stained_glass_pane",
				2 => "minecraft:hard_magenta_stained_glass_pane",
				3 => "minecraft:hard_light_blue_stained_glass_pane",
				4 => "minecraft:hard_yellow_stained_glass_pane",
				5 => "minecraft:hard_lime_stained_glass_pane",
				6 => "minecraft:hard_pink_stained_glass_pane",
				7 => "minecraft:hard_gray_stained_glass_pane",
				8 => "minecraft:hard_light_gray_stained_glass_pane",
				9 => "minecraft:hard_cyan_stained_glass_pane",
				10 => "minecraft:hard_purple_stained_glass_pane",
				11 => "minecraft:hard_blue_stained_glass_pane",
				12 => "minecraft:hard_brown_stained_glass_pane",
				13 => "minecraft:hard_green_stained_glass_pane",
				14 => "minecraft:hard_red_stained_glass_pane",
				15 => "minecraft:hard_black_stained_glass_pane",
			],
			"minecraft:spawn_egg" => [
				140 => "minecraft:breeze_spawn_egg",
				142 => "minecraft:armadillo_spawn_egg",
			],
		];
	}
}
