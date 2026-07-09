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

final class BlockStateUpdater_1_20_70 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::addTypeUpdater($context, "minecraft:double_wooden_slab", "wood_type", fn(string $type) : string => "minecraft:" . $type . "_double_slab");
		self::addTypeUpdater($context, "minecraft:leaves", "old_leaf_type", fn(string $type) : string => "minecraft:" . $type . "_leaves");
		self::addTypeUpdater($context, "minecraft:leaves2", "new_leaf_type", fn(string $type) : string => "minecraft:" . $type . "_leaves");
		self::addTypeUpdater($context, "minecraft:wooden_slab", "wood_type", fn(string $type) : string => "minecraft:" . $type . "_slab");

		$context->addUpdater(1, 20, 70)
			->match("name", "minecraft:wood")
			->edit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$bit = $states->getTag("stripped_bit");
				$states->removeTag("stripped_bit");
				$toggles = $bit instanceof ByteTag && $bit->getValue() === 1;

				$type = $states->getString("wood_type");
				$states->removeTag("wood_type");
				$helper->getRootTag()->setTag("name", new StringTag($toggles ? "minecraft:stripped_" . $type . "_wood" : "minecraft:" . $type . "_wood"));
			});

		// Vanilla does not use updater for this block for some reason
		$context->addUpdater(1, 20, 70, false)
			->match("name", "minecraft:grass")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:grass_block"));
			});
	}

	/**
	 * @phpstan-param \Closure(string) : string $rename
	 */
	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, \Closure $rename) : void{
		$context->addUpdater(1, 20, 70)
			->match("name", $identifier)
			->visit("states")
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($rename) : void{
				$helper->getRootTag()->setTag("name", new StringTag($rename($helper->getStringValue())));
			})
			->remove($typeState);
	}
}
