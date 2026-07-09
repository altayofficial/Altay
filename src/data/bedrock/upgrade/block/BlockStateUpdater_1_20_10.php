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
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_20_10 implements Updater{
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
				self::addTypeUpdater($context, "minecraft:concrete", "color", $color, "minecraft:light_gray_concrete");
				self::addTypeUpdater($context, "minecraft:shulker_box", "color", $color, "minecraft:light_gray_shulker_box");
			}else{
				self::addTypeUpdater($context, "minecraft:concrete", "color", $color, "minecraft:" . $color . "_concrete");
				self::addTypeUpdater($context, "minecraft:shulker_box", "color", $color, "minecraft:" . $color . "_shulker_box");
			}
		}

		self::addFacingDirectionUpdater($context, "minecraft:observer");
	}

	private static function observerDirections() : OrderedUpdater{
		return new OrderedUpdater(
			"facing_direction", "minecraft:facing_direction", 0,
			["down", "up", "north", "south", "west", "east"]
		);
	}

	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, string $type, string $newIdentifier) : void{
		$context->addUpdater(1, 20, 10)
			->match("name", $identifier)
			->visit("states")
			->match($typeState, $type)
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove($typeState);
	}

	private static function addFacingDirectionUpdater(CompoundTagUpdaterContext $context, string $identifier) : void{
		$updater = self::observerDirections();
		$context->addUpdater(1, 20, 10)
			->match("name", $identifier)
			->visit("states")
			->edit($updater->getOldProperty(), function(CompoundTagEditHelper $helper) use ($updater) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith($updater->getNewProperty(), new StringTag($updater->translate($value)));
			});
	}
}
