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
use function count;

final class BlockStateUpdater_1_19_80 implements Updater{
	use SingletonTrait;

	private const WOOD = [
		"birch",
		"oak",
		"jungle",
		"spruce",
		"acacia",
		"dark_oak"
	];

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		//It could be done the more clever way, but Mojang added 12 updaters, and so we do.
		for($i = 0; $i < count(self::WOOD); $i++){
			$type = self::WOOD[$i];
			self::addTypeUpdater($context, "minecraft:fence", "wood_type", $type, "minecraft:" . $type . "_fence");
			if($i < 4){
				self::addTypeUpdater($context, "minecraft:log", "old_log_type", $type, "minecraft:" . $type . "_log");
			}else{
				self::addTypeUpdater($context, "minecraft:log2", "new_log_type", $type, "minecraft:" . $type . "_log");
			}
		}
	}

	private static function addTypeUpdater(CompoundTagUpdaterContext $context, string $identifier, string $typeState, string $type, string $newIdentifier) : void{
		$context->addUpdater(1, 19, 80)
			->match("name", $identifier)
			->visit("states")
			->match($typeState, $type)
			->edit($typeState, function(CompoundTagEditHelper $helper) use ($newIdentifier) : void{
				$helper->getRootTag()->setTag("name", new StringTag($newIdentifier));
			})
			->remove($typeState);
	}
}
