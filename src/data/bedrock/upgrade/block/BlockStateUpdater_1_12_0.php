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

use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_12_0 implements Updater{
	use SingletonTrait;

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 12, 0)
			->match("name", "minecraft:coral_fan")
			->visit("states")
			->rename("direction", "coral_fan_direction");

		$context->addUpdater(1, 12, 0)
			->match("name", "minecraft:coral_fan_dead")
			->visit("states")
			->rename("direction", "coral_fan_direction");
	}
}
