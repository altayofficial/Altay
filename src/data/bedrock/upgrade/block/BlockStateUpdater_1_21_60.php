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
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_21_60 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$directionToCardinal = OrderedUpdater::directionToCardinal();
		self::addDirectionUpdater($context, "minecraft:acacia_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:acacia_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:bamboo_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:bamboo_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:birch_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:birch_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:cherry_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:cherry_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:crimson_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:crimson_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:dark_oak_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:dark_oak_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:exposed_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:iron_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:jungle_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:jungle_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:mangrove_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:mangrove_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:oxidized_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:pale_oak_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:pale_oak_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:spruce_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:spruce_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:warped_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:warped_fence_gate", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:waxed_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:waxed_exposed_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:waxed_oxidized_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:waxed_weathered_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:weathered_copper_door", $directionToCardinal);
		self::addDirectionUpdater($context, "minecraft:wooden_door", $directionToCardinal);
		$context->addUpdater(1, 21, 60)
			->match("name", "minecraft:creaking_heart")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$bit = $states->getTag("active");
				$states->removeTag("active");
				$active = $bit instanceof ByteTag && $bit->getValue() === 1;
				$helper->replaceWith("creaking_heart_state", new StringTag($active ? "awake" : "uprooted"));
			});
	}

	private static function addDirectionUpdater(CompoundTagUpdaterContext $context, string $identifier, OrderedUpdater $updater) : void{
		$context->addUpdater(1, 21, 60)
			->match("name", $identifier)
			->visit("states")
			->edit($updater->getOldProperty(), function(CompoundTagEditHelper $helper) use ($updater) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith($updater->getNewProperty(), new StringTag($updater->translate($value)));
			});
	}
}
