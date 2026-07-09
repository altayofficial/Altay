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

final class ItemUpdater_1_20_20 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 20];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:stained_glass" => [
				0 => "minecraft:white_stained_glass",
				1 => "minecraft:orange_stained_glass",
				2 => "minecraft:magenta_stained_glass",
				3 => "minecraft:light_blue_stained_glass",
				4 => "minecraft:yellow_stained_glass",
				5 => "minecraft:lime_stained_glass",
				6 => "minecraft:pink_stained_glass",
				7 => "minecraft:gray_stained_glass",
				8 => "minecraft:light_gray_stained_glass",
				9 => "minecraft:cyan_stained_glass",
				10 => "minecraft:purple_stained_glass",
				11 => "minecraft:blue_stained_glass",
				12 => "minecraft:brown_stained_glass",
				13 => "minecraft:green_stained_glass",
				14 => "minecraft:red_stained_glass",
				15 => "minecraft:black_stained_glass",
			],
			"minecraft:stained_glass_pane" => [
				0 => "minecraft:white_stained_glass_pane",
				1 => "minecraft:orange_stained_glass_pane",
				2 => "minecraft:magenta_stained_glass_pane",
				3 => "minecraft:light_blue_stained_glass_pane",
				4 => "minecraft:yellow_stained_glass_pane",
				5 => "minecraft:lime_stained_glass_pane",
				6 => "minecraft:pink_stained_glass_pane",
				7 => "minecraft:gray_stained_glass_pane",
				8 => "minecraft:light_gray_stained_glass_pane",
				9 => "minecraft:cyan_stained_glass_pane",
				10 => "minecraft:purple_stained_glass_pane",
				11 => "minecraft:blue_stained_glass_pane",
				12 => "minecraft:brown_stained_glass_pane",
				13 => "minecraft:green_stained_glass_pane",
				14 => "minecraft:red_stained_glass_pane",
				15 => "minecraft:black_stained_glass_pane",
			],
		];
	}
}
