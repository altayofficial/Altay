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
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_21_0 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 21, 0)
			->match("name", "minecraft:coral_block")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$type = $states->getString("coral_color");
				$states->removeTag("coral_color");
				$bit = $states->getTag("dead_bit");
				$states->removeTag("dead_bit");
				$dead = $bit instanceof ByteTag && $bit->getValue() === 1;

				$newName = match($type){
					"blue" => "minecraft:" . ($dead ? "dead_" : "") . "tube_coral_block",
					"pink" => "minecraft:" . ($dead ? "dead_" : "") . "brain_coral_block",
					"purple" => "minecraft:" . ($dead ? "dead_" : "") . "bubble_coral_block",
					"yellow" => "minecraft:" . ($dead ? "dead_" : "") . "horn_coral_block",
					default => "minecraft:" . ($dead ? "dead_" : "") . "fire_coral_block"
				};
				$helper->getRootTag()->setTag("name", new StringTag($newName));
			});

		self::addTypeUpdater($context, "minecraft:double_plant", "double_plant_type", fn(string $type) => match($type){
			"syringa" => "minecraft:lilac",
			"grass" => "minecraft:tall_grass",
			"fern" => "minecraft:large_fern",
			"rose" => "minecraft:rose_bush",
			"paeonia" => "minecraft:peony",
			default => "minecraft:sunflower"
		});

		self::addTypeUpdater($context, "minecraft:stone_block_slab", "stone_slab_type", fn(string $type) => match($type){
			"quartz" => "minecraft:quartz_slab",
			"wood" => "minecraft:petrified_oak_slab",
			"stone_brick" => "minecraft:stone_brick_slab",
			"brick" => "minecraft:brick_slab",
			"smooth_stone" => "minecraft:smooth_stone_slab",
			"sandstone" => "minecraft:sandstone_slab",
			"nether_brick" => "minecraft:nether_brick_slab",
			default => "minecraft:cobblestone_slab"
		});

		self::addTypeUpdater($context, "minecraft:tallgrass", "tall_grass_type", fn(string $type) => match($type){
			"fern" => "minecraft:fern",
			default => "minecraft:short_grass"
		});

		// These are not official updaters
		$context->addUpdater(1, 21, 0, false, false)
			->match("name", "minecraft:trial_spawner")
			->visit("states")
			->tryAdd("ominous", new ByteTag(0));

		$context->addUpdater(1, 21, 0, false, false)
			->match("name", "minecraft:vault")
			->visit("states")
			->tryAdd("ominous", new ByteTag(0));
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 21, 0)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}
}
