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

final class ItemUpdater_1_20_50 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 20, 50];
	}

	protected function getRemappedMetas() : array{
		return [
			"minecraft:planks" => [
				0 => "minecraft:oak_planks",
				1 => "minecraft:spruce_planks",
				2 => "minecraft:birch_planks",
				3 => "minecraft:jungle_planks",
				4 => "minecraft:acacia_planks",
				5 => "minecraft:dark_oak_planks",
			],
			"minecraft:stone" => [
				1 => "minecraft:granite",
				2 => "minecraft:polished_granite",
				3 => "minecraft:diorite",
				4 => "minecraft:polished_diorite",
				5 => "minecraft:andesite",
				6 => "minecraft:polished_andesite",
			],
		];
	}
}
