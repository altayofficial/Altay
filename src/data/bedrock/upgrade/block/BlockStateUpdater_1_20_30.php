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

final class BlockStateUpdater_1_20_30 implements Updater{
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
				self::addColorUpdater($context, "minecraft:stained_glass", $color, "minecraft:light_gray_stained_glass");
				self::addColorUpdater($context, "minecraft:stained_glass_pane", $color, "minecraft:light_gray_stained_glass_pane");
				self::addColorUpdater($context, "minecraft:concrete_powder", $color, "minecraft:light_gray_concrete_powder");
				self::addColorUpdater($context, "minecraft:stained_hardened_clay", $color, "minecraft:light_gray_terracotta");
			}else{
				self::addColorUpdater($context, "minecraft:stained_glass", $color, "minecraft:" . $color . "_stained_glass");
				self::addColorUpdater($context, "minecraft:stained_glass_pane", $color, "minecraft:" . $color . "_stained_glass_pane");
				self::addColorUpdater($context, "minecraft:concrete_powder", $color, "minecraft:" . $color . "_concrete_powder");
				self::addColorUpdater($context, "minecraft:stained_hardened_clay", $color, "minecraft:" . $color . "_terracotta");
			}
		}

		self::addDirectionUpdater($context, "minecraft:amethyst_cluster", OrderedUpdater::facingToBlock());
		self::addDirectionUpdater($context, "minecraft:medium_amethyst_bud", OrderedUpdater::facingToBlock());
		self::addDirectionUpdater($context, "minecraft:large_amethyst_bud", OrderedUpdater::facingToBlock());
		self::addDirectionUpdater($context, "minecraft:small_amethyst_bud", OrderedUpdater::facingToBlock());

		self::addDirectionUpdater($context, "minecraft:blast_furnace", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:furnace", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:lit_blast_furnace", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:lit_furnace", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:lit_smoker", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:smoker", OrderedUpdater::facingToCardinal());

		self::addDirectionUpdater($context, "minecraft:anvil", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:big_dripleaf", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:calibrated_sculk_sensor", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:campfire", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:end_portal_frame", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:lectern", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:pink_petals", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:powered_comparator", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:powered_repeater", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:small_dripleaf_block", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:soul_campfire", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:unpowered_comparator", OrderedUpdater::directionToCardinal());
		self::addDirectionUpdater($context, "minecraft:unpowered_repeater", OrderedUpdater::directionToCardinal());

		$context->addUpdater(1, 20, 30)
			->regex("name", "minecraft:.+slab(?:[2-4])?\\b")
			->visit("states")
			->edit("top_slot_bit", function(CompoundTagEditHelper $helper) : void{
				$value = $helper->getIntValue() === 1;

				if($value){
					$helper->replaceWith("minecraft:vertical_half", new StringTag("top"));
				}else{
					$helper->replaceWith("minecraft:vertical_half", new StringTag("bottom"));
				}
			});
	}

	private static function addColorUpdater(CompoundTagUpdaterContext $context, string $identifier, string $color, string $newIdentifier) : void{
		$context->addUpdater(1, 20, 30)
			->match("name", $identifier)
			->visit("states")
			->match("color", $color)
			->edit("color", function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove("color");
	}

	private static function addDirectionUpdater(CompoundTagUpdaterContext $context, string $identifier, OrderedUpdater $updater) : void{
		$context->addUpdater(1, 20, 30)
			->match("name", $identifier)
			->visit("states")
			->edit($updater->getOldProperty(), function(CompoundTagEditHelper $helper) use ($updater) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith($updater->getNewProperty(), new StringTag($updater->translate($value)));
			});
	}
}
