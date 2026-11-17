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

use PHPUnit\Framework\TestCase;
use pocketmine\math\Facing;

class SupportTypeTest extends TestCase{

	public function testFromShapeSlab() : void{
		$slab = VoxelShape::box(0, 0, 0, 1, 0.5, 1);
		self::assertSame(SupportType::FULL, SupportType::fromShape($slab, Facing::DOWN));
		self::assertSame(SupportType::NONE, SupportType::fromShape($slab, Facing::UP));
		self::assertSame(SupportType::NONE, SupportType::fromShape($slab, Facing::NORTH));
	}

	public function testFromShapePost() : void{
		$post = VoxelShape::box(6 / 16, 0, 6 / 16, 10 / 16, 1, 10 / 16);
		self::assertSame(SupportType::CENTER, SupportType::fromShape($post, Facing::UP));
		self::assertSame(SupportType::CENTER, SupportType::fromShape($post, Facing::DOWN));
		self::assertSame(SupportType::NONE, SupportType::fromShape($post, Facing::EAST));
	}

	public function testFromShapeRing() : void{
		//bowl with walls 2 pixels thick, like a hopper or cauldron rim
		$bowl = VoxelShape::box(0, 0, 0, 1, 0.25, 1)
			->union(VoxelShape::box(0, 0, 0, 2 / 16, 1, 1))
			->union(VoxelShape::box(14 / 16, 0, 0, 1, 1, 1))
			->union(VoxelShape::box(0, 0, 0, 1, 1, 2 / 16))
			->union(VoxelShape::box(0, 0, 14 / 16, 1, 1, 1));
		self::assertSame(SupportType::EDGE, SupportType::fromShape($bowl, Facing::UP));
		self::assertSame(SupportType::FULL, SupportType::fromShape($bowl, Facing::DOWN));

		//ring plus centre pillar, still has holes so it must not claim FULL
		$withPillar = $bowl->union(VoxelShape::box(7 / 16, 0, 7 / 16, 9 / 16, 1, 9 / 16));
		self::assertSame(SupportType::EDGE, SupportType::fromShape($withPillar, Facing::UP));
	}

	public function testFromShapeEmpty() : void{
		foreach(Facing::ALL as $facing){
			self::assertSame(SupportType::NONE, SupportType::fromShape(VoxelShape::empty(), $facing));
			self::assertSame(SupportType::FULL, SupportType::fromShape(VoxelShape::fullCube(), $facing));
		}
	}
}
