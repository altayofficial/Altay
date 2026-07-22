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

namespace pocketmine\world\format\io;

use pocketmine\world\format\io\exception\CorruptedWorldException;
use pocketmine\world\format\io\exception\UnsupportedWorldFormatException;

/**
 * @phpstan-type IsValid \Closure(string $path) : bool
 */
abstract class WorldProviderManagerEntry{

	/** @phpstan-param IsValid $isValid */
	protected function __construct(
		protected \Closure $isValid
	){}

	/**
	 * Tells if the path is a valid world.
	 * This must tell if the current format supports opening the files in the directory
	 */
	public function isValid(string $path) : bool{ return ($this->isValid)($path); }

	/**
	 * @throws CorruptedWorldException
	 * @throws UnsupportedWorldFormatException
	 */
	abstract public function fromPath(string $path, \Logger $logger) : WorldProvider;
}
