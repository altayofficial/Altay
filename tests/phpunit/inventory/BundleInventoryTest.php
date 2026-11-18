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
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Bundle;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class BundleInventoryTest extends TestCase{

	private SimpleInventory $holder;
	private BundleInventory $inventory;

	public function setUp() : void{
		$this->holder = new SimpleInventory(1);
		$this->holder->setItem(0, VanillaItems::BUNDLE()->setContents([
			VanillaItems::DIAMOND(),
			VanillaItems::APPLE(),
			VanillaItems::STICK()
		]));
		$this->inventory = new BundleInventory($this->holder, 0);
	}

	private function getBundle() : Bundle{
		$bundle = $this->holder->getItem(0);
		self::assertInstanceOf(Bundle::class, $bundle);
		return $bundle;
	}

	public function testChangesAreWrittenToTheBundle() : void{
		$this->inventory->setItem(1, VanillaItems::EMERALD());

		self::assertTrue($this->getBundle()->getContents()[1]->equals(VanillaItems::EMERALD()));
	}

	public function testRemovingShiftsTheRestDown() : void{
		$this->inventory->setItem(0, VanillaItems::AIR());

		$contents = $this->getBundle()->getContents();
		self::assertCount(2, $contents);
		self::assertTrue($contents[0]->equals(VanillaItems::APPLE()));
		self::assertTrue($contents[1]->equals(VanillaItems::STICK()));
	}

	public function testPlacingPastTheEndAppends() : void{
		$this->inventory->setItem(10, VanillaItems::EMERALD());

		$contents = $this->getBundle()->getContents();
		self::assertCount(4, $contents);
		self::assertTrue($contents[3]->equals(VanillaItems::EMERALD()));
	}

	public function testOverfillingIsRejected() : void{
		self::assertFalse($this->accepts(VanillaItems::DIAMOND_SWORD(), 3));
		self::assertFalse($this->accepts(VanillaBlocks::SHULKER_BOX()->asItem(), 3));
		self::assertTrue($this->accepts(VanillaItems::DIAMOND(), 3));
	}

	private function accepts(Item $item, int $slot) : bool{
		foreach($this->inventory->getSlotValidators() as $validator){
			if($validator->validate($this->inventory, $item, $slot) !== null){
				return false;
			}
		}
		return true;
	}

	public function testViewFollowsTheHolderSlot() : void{
		$this->holder->setItem(0, VanillaItems::BUNDLE());

		self::assertCount(0, $this->inventory->getContents());

		$this->holder->setItem(0, VanillaItems::DIAMOND());
		self::assertNull($this->inventory->getBundle());
	}
}
