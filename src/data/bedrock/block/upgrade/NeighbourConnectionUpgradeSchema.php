<?php

/*
 *
 *      _    _ _
 *     / \  | | |_ __ _ _   _
 *    / _ \ | | __/ _` | | | |
 *   / ___ \| | || (_| | |_| |
 *  /_/   \_\_|\__\__,_|\__, |
 *                       |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Original work by the PocketMine Team.
 * https://www.pocketmine.net/
 *
 * @author Altay Team
 * @link https://github.com/altayofficial
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\block\upgrade;

use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateStringValues;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;

final class NeighbourConnectionUpgradeSchema{

	public const SCHEMA_ID = 10001;

	private function __construct(){
	}

	public static function create() : BlockStateUpgradeSchema{
		$schema = new BlockStateUpgradeSchema(1, 26, 60, 0, self::SCHEMA_ID);

		foreach([
			BlockStateNames::MC_CONNECTION_NORTH,
			BlockStateNames::MC_CONNECTION_SOUTH,
			BlockStateNames::MC_CONNECTION_WEST,
			BlockStateNames::MC_CONNECTION_EAST,
			BlockStateNames::MC_CONNECTION_UP
		] as $name){
			$schema->addedProperties[BlockTypeNames::CHORUS_PLANT][$name] = new ByteTag(0);
			$schema->addedProperties[BlockTypeNames::FIRE][$name] = new ByteTag(0);
		}
		$schema->addedProperties[BlockTypeNames::CHORUS_PLANT][BlockStateNames::MC_CONNECTION_DOWN] = new ByteTag(0);

		$schema->addedProperties[BlockTypeNames::REDSTONE_WIRE] = [
			BlockStateNames::REDSTONE_NORTH => new StringTag(BlockStateStringValues::REDSTONE_NORTH_NONE),
			BlockStateNames::REDSTONE_SOUTH => new StringTag(BlockStateStringValues::REDSTONE_SOUTH_NONE),
			BlockStateNames::REDSTONE_WEST => new StringTag(BlockStateStringValues::REDSTONE_WEST_NONE),
			BlockStateNames::REDSTONE_EAST => new StringTag(BlockStateStringValues::REDSTONE_EAST_NONE)
		];

		return $schema;
	}
}
