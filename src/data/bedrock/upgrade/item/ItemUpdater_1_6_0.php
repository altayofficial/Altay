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

final class ItemUpdater_1_6_0 extends ItemUpdaterBase{
	use SingletonTrait;

	protected function getVersion() : array{
		return [1, 6, 0];
	}

	protected function getRenamedIds() : array{
		return [
			"minecraft:nametag" => "minecraft:name_tag",
			"minecraft:prismarineshard" => "minecraft:prismarine_shard",
			"minecraft:stone_slab" => "minecraft:double_stone_slab",
			"minecraft:stone_slab2" => "minecraft:double_stone_slab2",
			"minecraft:stone_slab3" => "minecraft:double_stone_slab3",
			"minecraft:stone_slab4" => "minecraft:double_stone_slab4",
		];
	}
}
