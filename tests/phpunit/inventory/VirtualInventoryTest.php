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

namespace pocketmine\inventory;

use PHPUnit\Framework\TestCase;
use function str_repeat;

class VirtualInventoryTest extends TestCase{

	public function testSizeLimitsAreAccepted() : void{
		self::assertSame(VirtualInventory::MIN_SIZE, (new VirtualInventory(VirtualInventory::MIN_SIZE, "min"))->getSize());
		self::assertSame(VirtualInventory::MAX_SIZE, (new VirtualInventory(VirtualInventory::MAX_SIZE, "max"))->getSize());
	}

	public function testZeroSizeIsRejected() : void{
		$this->expectException(\InvalidArgumentException::class);
		new VirtualInventory(0, "empty");
	}

	public function testOversizedIsRejected() : void{
		$this->expectException(\InvalidArgumentException::class);
		new VirtualInventory(VirtualInventory::MAX_SIZE + 1, "huge");
	}

	public function testLongTitleIsRejected() : void{
		$this->expectException(\InvalidArgumentException::class);
		new VirtualInventory(9, str_repeat("a", VirtualInventory::MAX_TITLE_LENGTH + 1));
	}

	public function testTitleIsKept() : void{
		self::assertSame("container.chest", (new VirtualInventory(27, "container.chest"))->getTitle());
	}

	public function testFiltersAreEmptyByDefault() : void{
		self::assertCount(0, (new VirtualInventory(9, "menu"))->getSlotChangeFilters()->toArray());
	}
}
