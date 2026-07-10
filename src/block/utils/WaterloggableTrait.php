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

namespace pocketmine\block\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Liquid;
use pocketmine\block\Water;

trait WaterloggableTrait{

	public function getContainedWater() : ?Water{
		$water = $this->getWaterlogging();
		return $water instanceof Water ? clone $water : null;
	}

	/** @return $this */
	public function setContainedWater(?Water $water) : self{
		$this->setWaterlogging($water !== null ? clone $water : null);
		return $this;
	}

	public function canBeWaterlogged() : bool{
		return true;
	}

	public function canContainLiquid(Liquid $liquid) : bool{
		return $this->canBeWaterlogged() && $liquid instanceof Water;
	}

	public function canBeWaterloggedByFlowingLiquid(Liquid $liquid) : bool{
		return $this->canBeWaterlogged() && $liquid instanceof Water && $this->hasTypeTag(BlockTypeTags::NON_SOURCE_WATERLOGGABLE);
	}

	public function isSideOpenToFlow(int $face) : bool{
		return true;
	}

	public function liquidCollide(Block $cause, Block $result) : bool{
		return $this->getWaterlogging()?->liquidCollide($cause, $result) ?? false;
	}
}
