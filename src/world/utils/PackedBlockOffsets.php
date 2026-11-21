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

namespace pocketmine\world\utils;

use function pack;
use function strlen;
use function strpos;
use function substr;
use function substr_replace;

/**
 * Edits lists of sub chunk block offsets packed as uint16 little endian, like the ones World keeps for random ticks.
 * Order doesn't matter in these lists, so removal swaps the last entry into the hole.
 *
 * @internal
 */
final class PackedBlockOffsets{

	private function __construct(){
		//NOOP
	}

	/**
	 * Returns the byte position of the offset in the list, or null if it isn't there.
	 */
	public static function find(string $list, int $offset) : ?int{
		$needle = pack("v", $offset);
		$position = strpos($list, $needle);
		//the bytes can also match across two neighbouring entries, so only even positions count
		while($position !== false && ($position & 1) !== 0){
			$position = strpos($list, $needle, $position + 1);
		}
		return $position === false ? null : $position;
	}

	public static function add(string $list, int $offset) : string{
		return self::find($list, $offset) === null ? $list . pack("v", $offset) : $list;
	}

	public static function remove(string $list, int $offset) : string{
		$position = self::find($list, $offset);
		if($position === null){
			return $list;
		}
		$last = strlen($list) - 2;
		if($position !== $last){
			$list = substr_replace($list, substr($list, $last, 2), $position, 2);
		}
		return substr($list, 0, $last);
	}
}
