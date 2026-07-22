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

namespace pocketmine\data\bedrock\block\convert\property;

/**
 * @phpstan-template TValue
 * @phpstan-template TRaw of int|string
 */
interface StateMap{

	/**
	 * @phpstan-return array<TRaw, TValue>
	 */
	public function getRawToValueMap() : array;

	/**
	 * @phpstan-param TValue $value
	 * @phpstan-return TRaw
	 */
	public function valueToRaw(mixed $value) : int|string;

	/**
	 * @phpstan-param TRaw $raw
	 * @phpstan-return TValue|null
	 */
	public function rawToValue(int|string $raw) : mixed;

	/**
	 * @phpstan-param TValue $value
	 */
	public function printableValue(mixed $value) : string;
}
