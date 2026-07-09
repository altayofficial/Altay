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

final class ItemUpdater_1_19_80 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 19, 80];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:boat" => [
				8 => "minecraft:cherry_boat",
			],
			"minecraft:chest_boat" => [
				8 => "minecraft:cherry_chest_boat",
			],
			"minecraft:fence" => [
				0 => "minecraft:oak_fence",
				1 => "minecraft:spruce_fence",
				2 => "minecraft:birch_fence",
				3 => "minecraft:jungle_fence",
				4 => "minecraft:acacia_fence",
				5 => "minecraft:dark_oak_fence",
			],
			"minecraft:log" => [
				0 => "minecraft:oak_log",
				1 => "minecraft:spruce_log",
				2 => "minecraft:birch_log",
				3 => "minecraft:jungle_log",
				5 => "minecraft:spruce_log",
				6 => "minecraft:birch_log",
				7 => "minecraft:jungle_log",
				9 => "minecraft:spruce_log",
				10 => "minecraft:birch_log",
				11 => "minecraft:jungle_log",
			],
			"minecraft:log2" => [
				0 => "minecraft:acacia_log",
				1 => "minecraft:dark_oak_log",
				5 => "minecraft:dark_oak_log",
				9 => "minecraft:dark_oak_log",
			],
		];
	}
}
