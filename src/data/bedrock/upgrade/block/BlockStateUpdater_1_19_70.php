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

final class BlockStateUpdater_1_19_70 implements Updater{
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
			self::addColorUpdater($context, $color);
		}
	}

	private static function addColorUpdater(CompoundTagUpdaterContext $context, string $color) : void{
		$context->addUpdater(1, 19, 70)
			->match("name", "minecraft:wool")
			->visit("states")
			->match("color", $color)
			->edit("color", function(CompoundTagEditHelper $helper) use ($color) : void{
				if($color === "silver"){
					$helper->getRootTag()->setTag("name", new StringTag("minecraft:light_gray_wool"));
				}else{
					$helper->getRootTag()->setTag("name", new StringTag("minecraft:" . $color . "_wool"));
				}
			})
			->remove("color");
	}
}
