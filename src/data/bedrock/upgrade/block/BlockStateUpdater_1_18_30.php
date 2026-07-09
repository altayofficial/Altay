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

final class BlockStateUpdater_1_18_30 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::renameIdentifier($context, "minecraft:concretePowder", "minecraft:concrete_powder");
		self::renameIdentifier($context, "minecraft:frog_egg", "minecraft:frog_spawn");
		self::renameIdentifier($context, "minecraft:invisibleBedrock", "minecraft:invisible_bedrock");
		self::renameIdentifier($context, "minecraft:movingBlock", "minecraft:moving_block");
		self::renameIdentifier($context, "minecraft:pistonArmCollision", "minecraft:piston_arm_collision");
		self::renameIdentifier($context, "minecraft:seaLantern", "minecraft:sea_lantern");
		self::renameIdentifier($context, "minecraft:stickyPistonArmCollision", "minecraft:sticky_piston_arm_collision");
		self::renameIdentifier($context, "minecraft:tripWire", "minecraft:trip_wire");

		self::addPillarAxis($context, "minecraft:ochre_froglight");
		self::addPillarAxis($context, "minecraft:pearlescent_froglight");
		self::addPillarAxis($context, "minecraft:verdant_froglight");
	}

	private static function renameIdentifier(CompoundTagUpdaterContext $context, string $from, string $to) : void{
		$context->addUpdater(1, 18, 10, true) //Here we go again Mojang
			->match("name", $from)
			->edit("name", function(CompoundTagEditHelper $helper) use ($to) : void{
				$helper->replaceWith("name", new StringTag($to));
			});
	}

	private static function addPillarAxis(CompoundTagUpdaterContext $context, string $from) : void{
		$context->addUpdater(1, 18, 10, true) //Here we go again Mojang
			->match("name", $from)
			->visit("states")
			->tryAdd("pillar_axis", new StringTag("y"));
	}
}
