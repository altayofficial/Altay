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

final class BlockStateUpdater_1_21_10 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::addTypeUpdater($context, "minecraft:stone_block_slab2", "stone_slab_type_2", fn(string $type) => match($type){
			"prismarine_rough" => "minecraft:prismarine_slab",
			"prismarine_dark" => "minecraft:dark_prismarine_slab",
			"smooth_sandstone" => "minecraft:smooth_sandstone_slab",
			"purpur" => "minecraft:purpur_slab",
			"red_nether_brick" => "minecraft:red_nether_brick_slab",
			"prismarine_brick" => "minecraft:prismarine_brick_slab",
			"mossy_cobblestone" => "minecraft:mossy_cobblestone_slab",
			default => "minecraft:red_sandstone_slab"
		});

		self::addTypeUpdater($context, "minecraft:stone_block_slab3", "stone_slab_type_3", fn(string $type) => match($type){
			"smooth_red_sandstone" => "minecraft:smooth_red_sandstone_slab",
			"polished_granite" => "minecraft:polished_granite_slab",
			"granite" => "minecraft:granite_slab",
			"polished_diorite" => "minecraft:polished_diorite_slab",
			"andesite" => "minecraft:andesite_slab",
			"polished_andesite" => "minecraft:polished_andesite_slab",
			"diorite" => "minecraft:diorite_slab",
			default => "minecraft:end_stone_brick_slab"
		});

		self::addTypeUpdater($context, "minecraft:stone_block_slab4", "stone_slab_type_4", fn(string $type) => match($type){
			"smooth_quartz" => "minecraft:smooth_quartz_slab",
			"cut_sandstone" => "minecraft:cut_sandstone_slab",
			"cut_red_sandstone" => "minecraft:cut_red_sandstone_slab",
			"stone" => "minecraft:normal_stone_slab",
			default => "minecraft:mossy_stone_brick_slab"
		});

		self::addTypeUpdater($context, "minecraft:double_stone_block_slab", "stone_slab_type", fn(string $type) => match($type){
			"quartz" => "minecraft:quartz_double_slab",
			"wood" => "minecraft:petrified_oak_double_slab",
			"stone_brick" => "minecraft:stone_brick_double_slab",
			"brick" => "minecraft:brick_double_slab",
			"sandstone" => "minecraft:sandstone_double_slab",
			"nether_brick" => "minecraft:nether_brick_double_slab",
			"cobblestone" => "minecraft:cobblestone_double_slab",
			default => "minecraft:smooth_stone_double_slab"
		});

		self::addTypeUpdater($context, "minecraft:double_stone_block_slab2", "stone_slab_type_2", fn(string $type) => match($type){
			"prismarine_rough" => "minecraft:prismarine_double_slab",
			"prismarine_dark" => "minecraft:dark_prismarine_double_slab",
			"smooth_sandstone" => "minecraft:smooth_sandstone_double_slab",
			"purpur" => "minecraft:purpur_double_slab",
			"red_nether_brick" => "minecraft:red_nether_brick_double_slab",
			"prismarine_brick" => "minecraft:prismarine_brick_double_slab",
			"mossy_cobblestone" => "minecraft:mossy_cobblestone_double_slab",
			default => "minecraft:red_sandstone_double_slab"
		});

		self::addTypeUpdater($context, "minecraft:double_stone_block_slab3", "stone_slab_type_3", fn(string $type) => match($type){
			"smooth_red_sandstone" => "minecraft:smooth_red_sandstone_double_slab",
			"polished_granite" => "minecraft:polished_granite_double_slab",
			"granite" => "minecraft:granite_double_slab",
			"polished_diorite" => "minecraft:polished_diorite_double_slab",
			"andesite" => "minecraft:andesite_double_slab",
			"polished_andesite" => "minecraft:polished_andesite_double_slab",
			"diorite" => "minecraft:diorite_double_slab",
			default => "minecraft:end_stone_brick_double_slab"
		});

		self::addTypeUpdater($context, "minecraft:double_stone_block_slab4", "stone_slab_type_4", fn(string $type) => match($type){
			"smooth_quartz" => "minecraft:smooth_quartz_double_slab",
			"cut_sandstone" => "minecraft:cut_sandstone_double_slab",
			"cut_red_sandstone" => "minecraft:cut_red_sandstone_double_slab",
			"stone" => "minecraft:normal_stone_double_slab",
			default => "minecraft:mossy_stone_brick_double_slab"
		});

		self::addTypeUpdater($context, "minecraft:prismarine", "prismarine_block_type", fn(string $type) => match($type){
			"bricks" => "minecraft:prismarine_bricks",
			"dark" => "minecraft:dark_prismarine",
			default => "minecraft:prismarine"
		});

		self::addCoralUpdater($context, "minecraft:coral_fan_hang", "tube_coral_wall_fan", "brain_coral_wall_fan");
		self::addCoralUpdater($context, "minecraft:coral_fan_hang2", "bubble_coral_wall_fan", "fire_coral_wall_fan");
		self::addCoralUpdater($context, "minecraft:coral_fan_hang3", "horn_coral_wall_fan", null);

		self::addTypeUpdater($context, "minecraft:monster_egg", "monster_egg_stone_type", fn(string $type) => match($type){
			"cobblestone" => "minecraft:infested_cobblestone",
			"stone_brick" => "minecraft:infested_stone_bricks",
			"mossy_stone_brick" => "minecraft:infested_mossy_stone_bricks",
			"cracked_stone_brick" => "minecraft:infested_cracked_stone_bricks",
			"chiseled_stone_brick" => "minecraft:infested_chiseled_stone_bricks",
			default => "minecraft:infested_stone"
		});

		self::addTypeUpdater($context, "minecraft:stonebrick", "stone_brick_type", fn(string $type) => match($type){
			"mossy" => "minecraft:mossy_stone_bricks",
			"cracked" => "minecraft:cracked_stone_bricks",
			"chiseled" => "minecraft:chiseled_stone_bricks",
			// return "minecraft:smooth_stone_bricks"; // TODO: does not seem to exists anymore
			default => "minecraft:stone_bricks"
		});
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 21, 10)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}

	private static function addCoralUpdater(CompoundTagUpdaterContext $context, string $identifier, string $type1, ?string $type2) : void{
		$context->addUpdater(1, 21, 10)
			->match("name", $identifier)
			->edit("states", function(CompoundTagEditHelper $helper) use ($type1, $type2) : void{
				$states = $helper->getCompoundTag();
				$deadBit = $states->getTag("dead_bit");
				$states->removeTag("dead_bit");
				$dead = $deadBit instanceof ByteTag && $deadBit->getValue() === 1;

				$typeBit = $states->getTag("coral_hang_type_bit");
				$states->removeTag("coral_hang_type_bit"); // always remove

				if($type2 === null){
					$type = $type1;
				}else{
					$type = ($typeBit instanceof ByteTag && $typeBit->getValue() === 1) ? $type2 : $type1;
				}
				$helper->getRootTag()->setTag("name", new StringTag($dead ? "minecraft:dead_" . $type : "minecraft:" . $type));
			});
	}
}
