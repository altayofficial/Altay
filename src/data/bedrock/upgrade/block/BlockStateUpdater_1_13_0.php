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

namespace pocketmine\data\bedrock\upgrade\block;

use pocketmine\data\bedrock\upgrade\CompoundTagEditHelper;
use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_13_0 implements Updater{
	use SingletonTrait;

	private const LEVER_DIRECTIONS = ["down_east_west", "east", "west", "south", "north", "up_north_south", "up_east_west", "down_north_south"];
	private const PILLAR_DIRECTION = ["y", "x", "z"];

	private static function registerLogUpdater(string $name, string $replace, CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 13, 0)
			->match("name", $name)
			->visit("states")
			->regex("direction", "[0-2]")
			->edit("direction", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("pillar_axis", new StringTag(self::PILLAR_DIRECTION[$value % 3]));
			});

		$context->addUpdater(1, 13, 0)
			->match("name", $name)
			->visit("states")
			->regex("direction", "[3]")
			->rename($replace, "wood_type")
			->edit("direction", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("pillar_axis", new StringTag(self::PILLAR_DIRECTION[$value % 3]));
			})
			->addByte("stripped_bit", 0)
			->popVisit()
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:wood"));
			});
	}

	private static function registerPillarUpdater(string $name, CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 13, 0)
			->match("name", $name)
			->visit("states")
			->edit("direction", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("pillar_axis", new StringTag(self::PILLAR_DIRECTION[$value % 3]));
			});

		$context->addUpdater(1, 13, 0)
			->match("name", $name)
			->visit("states")
			->tryAdd("pillar_axis", new StringTag(self::PILLAR_DIRECTION[0]));
	}

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 13, 0)
			->match("name", "minecraft:lever")
			->visit("states")
			->edit("facing_direction", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("lever_direction", new StringTag(self::LEVER_DIRECTIONS[$value]));
			});

		self::registerLogUpdater("minecraft:log", "old_log_type", $context);
		self::registerLogUpdater("minecraft:log2", "new_log_type", $context);

		self::registerPillarUpdater("minecraft:log", $context);
		self::registerPillarUpdater("minecraft:quartz_block", $context);
		self::registerPillarUpdater("minecraft:log2", $context);
		self::registerPillarUpdater("minecraft:purpur_block", $context);
		self::registerPillarUpdater("minecraft:bone_block", $context);
		self::registerPillarUpdater("minecraft:stripped_spruce_log", $context);
		self::registerPillarUpdater("minecraft:stripped_birch_log", $context);
		self::registerPillarUpdater("minecraft:stripped_jungle_log", $context);
		self::registerPillarUpdater("minecraft:stripped_acacia_log", $context);
		self::registerPillarUpdater("minecraft:stripped_dark_oak_log", $context);
		self::registerPillarUpdater("minecraft:stripped_oak_log", $context);
		self::registerPillarUpdater("minecraft:wood", $context);
		self::registerPillarUpdater("minecraft:hay_block", $context);

		$context->addUpdater(1, 13, 0)
			->match("name", "minecraft:end_rod")
			->visit("states")
			->regex("facing_direction", "[^0-5]")
			->remove("facing_direction")
			->addInt("block_light_level", 14)
			->popVisit()
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:light_block"));
			});

		$context->addUpdater(1, 13, 0)
			->regex("name", "minecraft:.+")
			->visit("states")
			->edit("facing_direction", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				if($value >= 6){
					$helper->replaceWith("facing_direction", new IntTag(0));
				}
			});

		$context->addUpdater(1, 13, 0)
			->regex("name", "minecraft:.+")
			->visit("states")
			->edit("fill_level", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				if($value >= 7){
					$helper->replaceWith("fill_level", new IntTag(6));
				}
			});
	}
}
