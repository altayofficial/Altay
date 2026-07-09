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

final class BlockStateUpdater_1_20_50 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 20, 50)
			->match("name", "minecraft:planks")
			->visit("states")
			->edit("wood_type", function(CompoundTagEditHelper $helper) : void{
				$type = $helper->getStringValue();
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:" . $type . "_planks"));
			})
			->remove("wood_type");

		$context->addUpdater(1, 20, 50)
			->match("name", "minecraft:stone")
			->visit("states")
			->edit("stone_type", function(CompoundTagEditHelper $helper) : void{
				$type = $helper->getStringValue();
				switch($type){
					case "andesite_smooth":
						$type = "polished_andesite";
						break;
					case "diorite_smooth":
						$type = "polished_diorite";
						break;
					case "granite_smooth":
						$type = "polished_granite";
						break;
				}
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:" . $type));
			})
			->remove("stone_type");
	}
}
