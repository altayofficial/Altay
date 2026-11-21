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

use PHPUnit\Framework\TestCase;
use function array_values;
use function pack;
use function sort;
use function unpack;

final class PackedBlockOffsetsTest extends TestCase{

	/**
	 * @return int[]
	 * @phpstan-return list<int>
	 */
	private static function unpackSorted(string $list) : array{
		$offsets = $list === "" ? [] : array_values(unpack("v*", $list) ?: []);
		sort($offsets);
		return $offsets;
	}

	public function testFindIgnoresMatchesAcrossEntries() : void{
		//0x0201 followed by 0x0403 contains the bytes of 0x0302 at an odd position
		$list = pack("v*", 0x0201, 0x0403);
		self::assertNull(PackedBlockOffsets::find($list, 0x0302));
		self::assertSame(0, PackedBlockOffsets::find($list, 0x0201));
		self::assertSame(2, PackedBlockOffsets::find($list, 0x0403));
	}

	public function testFindSkipsOddMatchBeforeRealEntry() : void{
		$list = pack("v*", 0x0201, 0x0403, 0x0302);
		self::assertSame(4, PackedBlockOffsets::find($list, 0x0302));
	}

	public function testAddDoesNotDuplicate() : void{
		$list = PackedBlockOffsets::add("", 5);
		$list = PackedBlockOffsets::add($list, 4095);
		$list = PackedBlockOffsets::add($list, 5);
		self::assertSame([5, 4095], self::unpackSorted($list));
	}

	public function testRemove() : void{
		$list = pack("v*", 1, 2, 3, 4);
		self::assertSame([1, 3, 4], self::unpackSorted(PackedBlockOffsets::remove($list, 2)));
		self::assertSame([1, 2, 3], self::unpackSorted(PackedBlockOffsets::remove($list, 4)));
		self::assertSame([1, 2, 3, 4], self::unpackSorted(PackedBlockOffsets::remove($list, 9)));
		self::assertSame("", PackedBlockOffsets::remove(pack("v", 7), 7));
	}
}
