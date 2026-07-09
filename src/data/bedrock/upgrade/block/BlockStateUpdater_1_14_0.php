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
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_14_0 implements Updater{
	use SingletonTrait;

	private static function addRailUpdater(string $name, CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 14, 0)
			->match("name", $name)
			->visit("states")
			->edit("rail_direction", function(CompoundTagEditHelper $helper) : void{
				$direction = $helper->getIntValue();
				if($direction > 5){
					$direction = 0;
				}
				$helper->replaceWith("rail_direction", new IntTag($direction));
			});
	}

	public static function addMaxStateUpdater(string $name, string $state, int $maxValue, CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 14, 0)
			->match("name", $name)
			->visit("states")
			->edit($state, function(CompoundTagEditHelper $helper) use ($state, $maxValue) : void{
				$value = $helper->getIntValue();
				if($value > $maxValue){
					$value = $maxValue;
				}
				$helper->replaceWith($state, new IntTag($value));
			});
	}

	private static function convertWeirdoDirectionToFacing(int $weirdoDirection) : int{
		switch($weirdoDirection){
			case 0:
				return 5;
			case 1:
				return 4;
			case 2:
				return 3;
			case 3:
			default:
				return 2;
		}
	}

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 14, 0)
			->match("name", "minecraft:frame")
			->visit("states")
			->edit("weirdo_direction", function(CompoundTagEditHelper $helper) : void{
				$tag = $helper->getIntValue();
				$newDirection = self::convertWeirdoDirectionToFacing($tag);
				$helper->replaceWith("facing_direction", new IntTag($newDirection));
			});

		self::addRailUpdater("minecraft:golden_rail", $context);
		self::addRailUpdater("minecraft:detector_rail", $context);
		self::addRailUpdater("minecraft:activator_rail", $context);

		self::addMaxStateUpdater("minecraft:rail", "rail_direction", 9, $context);
		self::addMaxStateUpdater("minecraft:cake", "bite_counter", 6, $context);
		self::addMaxStateUpdater("minecraft:chorus_flower", "age", 5, $context);
		self::addMaxStateUpdater("minecraft:cocoa", "age", 2, $context);
		self::addMaxStateUpdater("minecraft:composter", "composter_fill_level", 8, $context);
	}
}
