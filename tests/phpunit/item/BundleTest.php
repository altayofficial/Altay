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

namespace pocketmine\item;

use PHPUnit\Framework\TestCase;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;

class BundleTest extends TestCase{

	public function testWeight() : void{
		$bundle = VanillaItems::BUNDLE()->setContents([
			VanillaBlocks::DIRT()->asItem()->setCount(32),
			VanillaItems::ENDER_PEARL()->setCount(4)
		]);

		self::assertSame(32 + 16, $bundle->getWeight());
		self::assertSame(16, $bundle->getRemainingWeight());
		self::assertSame(16, $bundle->getAddableItemQuantity(VanillaBlocks::DIRT()->asItem()));
		self::assertSame(0, $bundle->getAddableItemQuantity(VanillaItems::DIAMOND_SWORD()));
	}

	public function testNestedBundleWeight() : void{
		$inner = VanillaItems::BUNDLE()->setContents([VanillaBlocks::DIRT()->asItem()->setCount(8)]);

		self::assertSame(4 + 8, Bundle::getItemWeight($inner));
	}

	public function testShulkerBoxesCannotBeStored() : void{
		self::assertFalse(Bundle::canStore(VanillaBlocks::SHULKER_BOX()->asItem()));
		self::assertFalse(Bundle::canStore(VanillaBlocks::DYED_SHULKER_BOX()->asItem()));
		self::assertTrue(Bundle::canStore(VanillaItems::BUNDLE()));
	}

	public function testSerializationKeepsContentsAndOrder() : void{
		$bundle = VanillaItems::BUNDLE()->setColor(DyeColor::RED)->setContents([
			VanillaItems::DIAMOND()->setCount(3),
			VanillaBlocks::DIRT()->asItem()->setCount(10),
			VanillaItems::BUNDLE()->setContents([VanillaItems::APPLE()])
		]);

		$copy = Item::nbtDeserialize($bundle->nbtSerialize());

		self::assertInstanceOf(Bundle::class, $copy);
		self::assertSame(DyeColor::RED, $copy->getColor());
		self::assertTrue($copy->equalsExact($bundle));
		$contents = $copy->getContents();
		self::assertCount(3, $contents);
		self::assertTrue($contents[0]->equalsExact(VanillaItems::DIAMOND()->setCount(3)));
		self::assertInstanceOf(Bundle::class, $contents[2]);
		self::assertCount(1, $contents[2]->getContents());
	}

	public function testPlainBundleHasNoColor() : void{
		$copy = Item::nbtDeserialize(VanillaItems::BUNDLE()->nbtSerialize());

		self::assertInstanceOf(Bundle::class, $copy);
		self::assertNull($copy->getColor());
	}

	public function testCloneDoesNotShareContents() : void{
		$inner = VanillaItems::BUNDLE();
		$bundle = VanillaItems::BUNDLE()->setContents([$inner]);
		$copy = clone $bundle;

		$copy->setContents([]);
		self::assertCount(1, $bundle->getContents());
	}
}
