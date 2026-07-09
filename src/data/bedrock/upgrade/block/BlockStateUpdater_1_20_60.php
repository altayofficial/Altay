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

final class BlockStateUpdater_1_20_60 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 20, 60)
			->match("name", "minecraft:hard_stained_glass")
			->visit("states")
			->edit("color", function(CompoundTagEditHelper $helper) : void{
				$color = $helper->getStringValue();
				if($color === "silver"){
					$color = "light_gray";
				}
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:hard_" . $color . "_stained_glass"));
			})
			->remove("color");

		$context->addUpdater(1, 20, 60)
			->match("name", "minecraft:hard_stained_glass_pane")
			->visit("states")
			->edit("color", function(CompoundTagEditHelper $helper) : void{
				$color = $helper->getStringValue();
				if($color === "silver"){
					$color = "light_gray";
				}
				$helper->getRootTag()->setTag("name", new StringTag("minecraft:hard_" . $color . "_stained_glass_pane"));
			})
			->remove("color");
	}
}
