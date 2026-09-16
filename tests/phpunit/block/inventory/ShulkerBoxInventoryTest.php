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

namespace pocketmine\block\inventory;

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\Position;

class ShulkerBoxInventoryTest extends TestCase{

	private function createInventory() : ShulkerBoxInventory{
		return new ShulkerBoxInventory(new Position(0, 0, 0, null));
	}

	public function testRejectsNestedShulkerBoxes() : void{
		$inventory = $this->createInventory();
		$shulker = VanillaBlocks::SHULKER_BOX()->asItem();
		$dyed = VanillaBlocks::DYED_SHULKER_BOX()->asItem();

		self::assertFalse($inventory->canAddItem($shulker));
		self::assertFalse($inventory->canAddItem($dyed));
		self::assertSame(0, $inventory->getAddableItemQuantity($shulker));
		self::assertSame(0, $inventory->getAddableItemQuantity($dyed));
		self::assertNotEmpty($inventory->addItem($shulker));
		self::assertNotEmpty($inventory->addItem($dyed));
		self::assertTrue($inventory->isSlotEmpty(0));
	}

	public function testStillAcceptsNormalItems() : void{
		$inventory = $this->createInventory();
		$dirt = VanillaBlocks::DIRT()->asItem()->setCount(16);

		self::assertTrue($inventory->canAddItem($dirt));
		self::assertSame(16, $inventory->getAddableItemQuantity($dirt));
		self::assertEmpty($inventory->addItem($dirt));
		self::assertTrue($inventory->getItem(0)->equalsExact($dirt));
	}
}
