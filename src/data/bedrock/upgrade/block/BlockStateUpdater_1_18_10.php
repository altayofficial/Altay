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
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_18_10 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 18, 10)
			->match("name", "minecraft:skull")
			->visit("states")
			->remove("no_drop_bit");

		$context->addUpdater(1, 18, 10)
			->match("name", "minecraft:glow_lichen")
			->visit("states")
			->tryEdit("multi_face_direction_bits", function(CompoundTagEditHelper $helper) : void{
				$bits = $helper->getIntValue();
				$north = ($bits & (1 << 2)) !== 0;
				$south = ($bits & (1 << 3)) !== 0;
				$west = ($bits & (1 << 4)) !== 0;
				if($north){
					$bits |= 1 << 4;
				}else{
					$bits &= ~(1 << 4);
				}
				if($south){
					$bits |= 1 << 2;
				}else{
					$bits &= ~(1 << 2);
				}
				if($west){
					$bits |= 1 << 3;
				}else{
					$bits &= ~(1 << 3);
				}
				$helper->replaceWith("multi_face_direction_bits", new IntTag($bits));
			});
	}
}
