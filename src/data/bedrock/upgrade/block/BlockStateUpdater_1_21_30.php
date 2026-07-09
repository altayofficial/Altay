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

final class BlockStateUpdater_1_21_30 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::addTypeUpdater($context, "minecraft:chemistry_table", "chemistry_table_type", fn(string $type) => "minecraft:" . $type);

		self::addTypeUpdater($context, "minecraft:cobblestone_wall", "wall_block_type", fn(string $type) => match($type){
			"prismarine" => "minecraft:prismarine_wall",
			"red_sandstone" => "minecraft:red_sandstone_wall",
			"mossy_stone_brick" => "minecraft:mossy_stone_brick_wall",
			"mossy_cobblestone" => "minecraft:mossy_cobblestone_wall",
			"sandstone" => "minecraft:sandstone_wall",
			"nether_brick" => "minecraft:nether_brick_wall",
			"granite" => "minecraft:granite_wall",
			"red_nether_brick" => "minecraft:red_nether_brick_wall",
			"stone_brick" => "minecraft:stone_brick_wall",
			"end_brick" => "minecraft:end_stone_brick_wall",
			"brick" => "minecraft:brick_wall",
			"andesite" => "minecraft:andesite_wall",
			"diorite" => "minecraft:diorite_wall",
			default => "minecraft:cobblestone_wall"
		});

		$context->addUpdater(1, 21, 30)
			->match("name", "minecraft:colored_torch_bp")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$bit = $states->getTag("color_bit");
				$states->removeTag("color_bit");
				$toggled = $bit instanceof ByteTag && $bit->getValue() === 1;
				$helper->getRootTag()->setTag("name", new StringTag($toggled ? "minecraft:colored_torch_purple" : "minecraft:colored_torch_blue"));
			});

		$context->addUpdater(1, 21, 30)
			->match("name", "minecraft:colored_torch_rg")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$bit = $states->getTag("color_bit");
				$states->removeTag("color_bit");
				$toggled = $bit instanceof ByteTag && $bit->getValue() === 1;
				$helper->getRootTag()->setTag("name", new StringTag($toggled ? "minecraft:colored_torch_red" : "minecraft:colored_torch_green"));
			});

		self::addTypeUpdater($context, "minecraft:purpur_block", "chisel_type", fn(string $type) => match($type){
			"lines" => "minecraft:purpur_pillar", // chiseled, smooth were deprecated
			default => "minecraft:purpur_block"
		});

		self::addTypeUpdater($context, "minecraft:sponge", "sponge_type", fn(string $type) => match($type){
			"wet" => "minecraft:wet_sponge",
			default => "minecraft:sponge"
		});

		self::addTypeUpdater($context, "minecraft:structure_void", "structure_void_type", fn(string $type) => "minecraft:structure_void"); // air was removed

		$context->addUpdater(1, 21, 30)
			->match("name", "minecraft:tnt")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$allowUnderwater = $states->getTag("allow_underwater_bit");
				$states->removeTag("allow_underwater_bit");
				$toggled = $allowUnderwater instanceof ByteTag && $allowUnderwater->getValue() === 1;
				$helper->getRootTag()->setTag("name", new StringTag($toggled ? "minecraft:tnt" : "minecraft:underwater_tnt"));
			});
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 21, 30)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}
}
