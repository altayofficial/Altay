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

/**
 * This trait is used for blocks that have an age property.
 * Need to add to the block the constant MAX_AGE.
 */
trait AgeableTrait{
	protected int $age = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_AGE, $this->age);
	}

	public function getAge() : int{ return $this->age; }

	public function getMaxAge() : int{ return self::MAX_AGE; }

	/**
	 * @return $this
	 */
	public function setAge(int $age) : self{
		if($age < 0 || $age > self::MAX_AGE){
			throw new \InvalidArgumentException("Age must be in range 0 ... " . self::MAX_AGE);
		}
		$this->age = $age;
		return $this;
	}
}
