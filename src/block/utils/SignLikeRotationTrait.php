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

namespace pocketmine\block\utils;

use pocketmine\data\runtime\RuntimeDataDescriber;
use function floor;

trait SignLikeRotationTrait{
	/** @var int */
	private $rotation = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, 15, $this->rotation);
	}

	public function getRotation() : int{ return $this->rotation; }

	/** @return $this */
	public function setRotation(int $rotation) : self{
		if($rotation < 0 || $rotation > 15){
			throw new \InvalidArgumentException("Rotation must be in range 0-15");
		}
		$this->rotation = $rotation;
		return $this;
	}

	private static function getRotationFromYaw(float $yaw) : int{
		return ((int) floor((($yaw + 180) * 16 / 360) + 0.5)) & 0xf;
	}
}
