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

use pocketmine\math\Facing;

/**
 * Implemented by blocks which connect to their horizontal neighbours, such as fences and glass panes.
 */
interface HorizontalConnections{

	/**
	 * @return HorizontalFacingOption[]
	 * @phpstan-return array<int, HorizontalFacingOption>
	 */
	public function getConnections() : array;

	public function isConnected(Facing $facing) : bool;

	/**
	 * @return $this
	 */
	public function setConnected(Facing $facing, bool $value) : self;

	/**
	 * @param HorizontalFacingOption[] $connections
	 * @phpstan-param array<HorizontalFacingOption> $connections
	 *
	 * @return $this
	 */
	public function setConnections(array $connections) : self;

}
