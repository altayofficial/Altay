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

final class BlockStateUpdater_1_21_40 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		// Skull blocks was now split into individual blocks
		// however, skull type was determined by block entity or item data, and we do not have that information here
		$context->addUpdater(1, 21, 40)
			->match("name", "minecraft:skull")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:skeleton_skull"));
			});
		// these are not vanilla updaters
		// but use this one to bump the version to 18163713 as that's what vanilla does
		$context->addUpdater(1, 21, 40)
			->match("name", "minecraft:cherry_wood")
			->visit("states")
			->remove("stripped_bit");
		$context->addUpdater(1, 21, 40, false, false)
			->match("name", "minecraft:mangrove_wood")
			->visit("states")
			->remove("stripped_bit");

		// vanilla split mushroom stems into their own block in 1.21.40
		foreach(["minecraft:brown_mushroom_block", "minecraft:red_mushroom_block"] as $id){
			$context->addUpdater(1, 21, 40)
				->match("name", $id)
				->visit("states")
				->regex("huge_mushroom_bits", "10|15")
				->popVisit()
				->edit("name", function(CompoundTagEditHelper $helper) : void{
					$helper->replaceWith("name", new StringTag("minecraft:mushroom_stem"));
				});
		}
	}
}
