<?php

/*
 *
 *      _    _ _
 *     / \  | | |_ __ _ _   _
 *    / _ \ | | __/ _` | | | |
 *   / ___ \| | || (_| | |_| |
 *  /_/   \_\_|\__\__,_|\__, |
 *                       |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Original work by the PocketMine Team.
 * https://www.pocketmine.net/
 *
 * @author Altay Team
 * @link https://github.com/altayofficial
 */

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\RayTraceResult;

abstract class Throwable extends Projectile{

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.25, 0.25); }

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.03; }

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);
		$this->flagForDespawn();
	}
}
