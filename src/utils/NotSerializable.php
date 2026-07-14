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

namespace pocketmine\utils;

trait NotSerializable{

	/** @return mixed[] */
	final public function __serialize() : array{
		throw new \LogicException("Serialization of " . static::class . " objects is not allowed");
	}

	/** @param mixed[] $data */
	final public function __unserialize(array $data) : void{
		throw new \LogicException("Unserialization of " . static::class . " objects is not allowed");
	}
}
