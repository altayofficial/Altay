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

namespace pocketmine\block;

use PHPUnit\Framework\TestCase;
use pocketmine\block\utils\BrewingStandSlot;
use function count;

class BrewingStandTest extends TestCase{

	/**
	 * @phpstan-return \Generator<int, array{list<BrewingStandSlot>}, void, void>
	 */
	public static function slotsProvider() : \Generator{
		yield [BrewingStandSlot::cases()];
		yield [[BrewingStandSlot::EAST]];
		yield [[BrewingStandSlot::EAST, BrewingStandSlot::NORTHWEST]];
	}

	/**
	 * @dataProvider slotsProvider
	 *
	 * @param BrewingStandSlot[] $slots
	 * @phpstan-param list<BrewingStandSlot> $slots
	 */
	public function testHasAndSetSlot(array $slots) : void{
		$block = VanillaBlocks::BREWING_STAND();
		foreach($slots as $slot){
			$block->setSlot($slot, true);
		}
		foreach($slots as $slot){
			self::assertTrue($block->hasSlot($slot));
		}

		foreach($slots as $slot){
			$block->setSlot($slot, false);
		}
		foreach($slots as $slot){
			self::assertFalse($block->hasSlot($slot));
		}
	}

	/**
	 * @dataProvider slotsProvider
	 *
	 * @param BrewingStandSlot[] $slots
	 * @phpstan-param list<BrewingStandSlot> $slots
	 */
	public function testGetSlots(array $slots) : void{
		$block = VanillaBlocks::BREWING_STAND();

		foreach($slots as $slot){
			$block->setSlot($slot, true);
		}

		self::assertCount(count($slots), $block->getSlots());

		foreach($slots as $slot){
			$block->setSlot($slot, false);
		}
		self::assertCount(0, $block->getSlots());
	}

	/**
	 * @dataProvider slotsProvider
	 *
	 * @param BrewingStandSlot[] $slots
	 * @phpstan-param list<BrewingStandSlot> $slots
	 */
	public function testSetSlots(array $slots) : void{
		$block = VanillaBlocks::BREWING_STAND();

		$block->setSlots($slots);
		foreach($slots as $slot){
			self::assertTrue($block->hasSlot($slot));
		}
		$block->setSlots([]);
		self::assertCount(0, $block->getSlots());
		foreach($slots as $slot){
			self::assertFalse($block->hasSlot($slot));
		}
	}
}
