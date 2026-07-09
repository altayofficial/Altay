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
use pocketmine\data\bedrock\upgrade\OrderedUpdater;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_20_0 implements Updater{
	use SingletonTrait;

	private const COLORS = [
		"magenta",
		"pink",
		"green",
		"lime",
		"yellow",
		"black",
		"light_blue",
		"brown",
		"cyan",
		"orange",
		"red",
		"gray",
		"white",
		"blue",
		"purple",
		"silver"
	];

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		foreach(self::COLORS as $color){
			if($color === "silver"){
				self::addTypeUpdater($context, "minecraft:carpet", "color", $color, "minecraft:light_gray_carpet");
			}else{
				self::addTypeUpdater($context, "minecraft:carpet", "color", $color, "minecraft:" . $color . "_carpet");
			}
		}

		self::addCoralUpdater($context, "red", "minecraft:fire_coral");
		self::addCoralUpdater($context, "pink", "minecraft:brain_coral");
		self::addCoralUpdater($context, "blue", "minecraft:tube_coral");
		self::addCoralUpdater($context, "yellow", "minecraft:horn_coral");
		self::addCoralUpdater($context, "purple", "minecraft:bubble_coral");

		$context->addUpdater(1, 20, 0)
			->match("name", "minecraft:calibrated_sculk_sensor")
			->visit("states")
			->edit("powered_bit", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("sculk_sensor_phase", new IntTag($value));
			});

		$context->addUpdater(1, 20, 0)
			->match("name", "minecraft:sculk_sensor")
			->visit("states")
			->edit("powered_bit", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith("sculk_sensor_phase", new IntTag($value));
			});

		self::addPumpkinUpdater($context, "minecraft:carved_pumpkin");
		self::addPumpkinUpdater($context, "minecraft:lit_pumpkin");
		self::addPumpkinUpdater($context, "minecraft:pumpkin");

		self::addCauldronUpdater($context, "water");
		self::addCauldronUpdater($context, "lava");
		self::addCauldronUpdater($context, "powder_snow");
	}

	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, string $type, string $newIdentifier) : void{
		$context->addUpdater(1, 20, 0)
			->match("name", $identifier)
			->visit("states")
			->match($typeState, $type)
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove($typeState);
	}

	private static function addPumpkinUpdater(CompoundTagUpdaterContext $context, string $identifier) : void{
		$updater = OrderedUpdater::directionToCardinal();
		$context->addUpdater(1, 20, 0)
			->match("name", $identifier)
			->visit("states")
			->edit($updater->getOldProperty(), function(CompoundTagEditHelper $helper) use ($updater) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith($updater->getNewProperty(), new StringTag($updater->translate($value))); //Don't ask me why namespace is in vanilla state
			});
	}

	private static function addCauldronUpdater(CompoundTagUpdaterContext $context, string $type) : void{
		$context->addUpdater(1, 20, 0)
			->match("name", "minecraft:lava_cauldron")
			->visit("states")
			->match("cauldron_liquid", $type)
			->popVisit()
			->tryEdit("states", function(CompoundTagEditHelper $helper) use ($type) : void{
				$helper->getCompoundTag()->setTag("cauldron_liquid", new StringTag($type));
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:cauldron"));
			});
	}

	private static function addCoralUpdater(CompoundTagUpdaterContext $context, string $type, string $newIdentifier) : void{
		//Two updates to match final version
		$context->addUpdater(1, 20, 0)
			->match("name", "minecraft:coral")
			->visit("states")
			->match("coral_color", $type)
			->match("dead_bit", "0")
			->edit("coral_color", function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove("coral_color")
			->remove("dead_bit");

		$context->addUpdater(1, 20, 0)
			->match("name", "minecraft:coral")
			->visit("states")
			->match("coral_color", $type)
			->match("dead_bit", "1")
			->edit("coral_color", function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove("coral_color")
			->remove("dead_bit");
	}
}
