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
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_19_0 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::renameIdentifier($context, "minecraft:stone_slab", "minecraft:stone_block_slab");
		self::renameIdentifier($context, "minecraft:stone_slab2", "minecraft:stone_block_slab2");
		self::renameIdentifier($context, "minecraft:stone_slab3", "minecraft:stone_block_slab3");
		self::renameIdentifier($context, "minecraft:stone_slab4", "minecraft:stone_block_slab4");
		self::renameIdentifier($context, "minecraft:double_stone_slab", "minecraft:double_stone_block_slab");
		self::renameIdentifier($context, "minecraft:double_stone_slab2", "minecraft:double_stone_block_slab2");
		self::renameIdentifier($context, "minecraft:double_stone_slab3", "minecraft:double_stone_block_slab3");
		self::renameIdentifier($context, "minecraft:double_stone_slab4", "minecraft:double_stone_block_slab4");

		self::addProperty($context, "minecraft:sculk_shrieker", "can_summon", new ByteTag(0));
	}

	private static function renameIdentifier(CompoundTagUpdaterContext $context, string $from, string $to) : void{
		$context->addUpdater(1, 18, 10, true) //Here we go again Mojang
			->match("name", $from)
			->edit("name", function(CompoundTagEditHelper $helper) use ($to) : void{
				$helper->replaceWith("name", new StringTag($to));
			});
	}

	private static function addProperty(CompoundTagUpdaterContext $context, string $identifier, string $propertyName, Tag $value) : void{
		$context->addUpdater(1, 18, 10, true) //Here we go again Mojang
			->match("name", $identifier)
			->visit("states")
			->tryAdd($propertyName, $value);
	}
}
