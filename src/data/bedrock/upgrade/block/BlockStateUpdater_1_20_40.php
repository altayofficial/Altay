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

final class BlockStateUpdater_1_20_40 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		self::addDirectionUpdater($context, "minecraft:chest", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:ender_chest", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:stonecutter_block", OrderedUpdater::facingToCardinal());
		self::addDirectionUpdater($context, "minecraft:trapped_chest", OrderedUpdater::facingToCardinal());
	}

	private static function addDirectionUpdater(CompoundTagUpdaterContext $context, string $identifier, OrderedUpdater $updater) : void{
		$context->addUpdater(1, 20, 40)
			->match("name", $identifier)
			->visit("states")
			->edit($updater->getOldProperty(), function(CompoundTagEditHelper $helper) use ($updater) : void{
				$value = $helper->getIntValue();
				$helper->replaceWith($updater->getNewProperty(), new StringTag($updater->translate($value)));
			});
	}
}
