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

final class BlockStateUpdater_1_21_20 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 21, 20)
			->match("name", "minecraft:light_block")
			->visit("states")
			->edit("block_light_level", function(CompoundTagEditHelper $helper) : void{
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:light_block_" . $helper->getIntValue()));
			})
			->remove("block_light_level");

		self::addTypeUpdater($context, "minecraft:sandstone", "sand_stone_type", fn(string $type) => match($type){
			"cut" => "minecraft:cut_sandstone",
			"heiroglyphs" => "minecraft:chiseled_sandstone",
			"smooth" => "minecraft:smooth_sandstone",
			default => "minecraft:sandstone"
		});

		self::addTypeUpdater($context, "minecraft:quartz_block", "chisel_type", fn(string $type) => match($type){
			"chiseled" => "minecraft:chiseled_quartz_block",
			"lines" => "minecraft:quartz_pillar",
			"smooth" => "minecraft:smooth_quartz",
			default => "minecraft:quartz_block"
		});

		self::addTypeUpdater($context, "minecraft:red_sandstone", "sand_stone_type", fn(string $type) => match($type){
			"cut" => "minecraft:cut_red_sandstone",
			"heiroglyphs" => "minecraft:chiseled_red_sandstone",
			"smooth" => "minecraft:smooth_red_sandstone",
			default => "minecraft:red_sandstone"
		});

		self::addTypeUpdater($context, "minecraft:sand", "sand_type", fn(string $type) => match($type){
			"red" => "minecraft:red_sand",
			default => "minecraft:sand"
		});

		self::addTypeUpdater($context, "minecraft:dirt", "dirt_type", fn(string $type) => match($type){
			"coarse" => "minecraft:coarse_dirt",
			default => "minecraft:dirt"
		});

		self::addTypeUpdater($context, "minecraft:anvil", "damage", fn(string $type) => match($type){
			"broken" => "minecraft:damaged_anvil",
			"slightly_damaged" => "minecraft:chipped_anvil",
			"very_damaged" => "minecraft:deprecated_anvil",
			default => "minecraft:anvil"
		});

		// Vanilla does not use updater for this block for some reason
		$context->addUpdater(1, 21, 20, false, false)
			->match("name", "minecraft:yellow_flower")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:dandelion"));
			});
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 21, 20)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}
}
