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
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_20_80 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::addTypeUpdater($context, "minecraft:sapling", "sapling_type", fn(string $type) : string => "minecraft:" . $type . "_sapling");
		self::addTypeUpdater($context, "minecraft:red_flower", "flower_type", fn(string $type) : string => match($type){
			"tulip_orange" => "minecraft:orange_tulip",
			"tulip_pink" => "minecraft:pink_tulip",
			"tulip_white" => "minecraft:white_tulip",
			"tulip_red" => "minecraft:red_tulip",
			"oxeye" => "minecraft:oxeye_daisy",
			"orchid" => "minecraft:blue_orchid",
			"houstonia" => "minecraft:azure_bluet",
			default => "minecraft:" . $type
		});

		self::addTypeUpdater($context, "minecraft:coral_fan", "coral_color", fn(string $type) : string => match($type){
			"blue" => "minecraft:tube_coral_fan",
			"pink" => "minecraft:brain_coral_fan",
			"purple" => "minecraft:bubble_coral_fan",
			"yellow" => "minecraft:horn_coral_fan",
			default => "minecraft:fire_coral_fan"
		});

		self::addTypeUpdater($context, "minecraft:coral_fan_dead", "coral_color", fn(string $type) : string => match($type){
			"blue" => "minecraft:dead_tube_coral_fan",
			"pink" => "minecraft:dead_brain_coral_fan",
			"purple" => "minecraft:dead_bubble_coral_fan",
			"yellow" => "minecraft:dead_horn_coral_fan",
			default => "minecraft:dead_fire_coral_fan"
		});

		// This is not official updater, but they correctly removed sapling_type
		$context->addUpdater(1, 20, 80, false, false)
			->match("name", "minecraft:bamboo_sapling")
			->visit("states")
			->remove("sapling_type");
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 20, 80)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}
}
