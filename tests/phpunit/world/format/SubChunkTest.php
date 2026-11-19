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

namespace pocketmine\world\format;

use PHPUnit\Framework\TestCase;
use pocketmine\data\bedrock\BiomeIds;

class SubChunkTest extends TestCase{

	/**
	 * Test that a cloned SubChunk instance doesn't influence the original
	 */
	public function testClone() : void{
		$sub1 = new SubChunk(0, [], new PalettedBlockArray(BiomeIds::OCEAN));

		$sub1->setBlockStateId(0, 0, 0, 1);
		$sub1->getBlockLightArray()->set(0, 0, 0, 1);
		$sub1->getBlockSkyLightArray()->set(0, 0, 0, 1);

		$sub2 = clone $sub1;

		$sub2->setBlockStateId(0, 0, 0, 2);
		$sub2->getBlockLightArray()->set(0, 0, 0, 2);
		$sub2->getBlockSkyLightArray()->set(0, 0, 0, 2);

		self::assertNotSame($sub1->getBlockStateId(0, 0, 0), $sub2->getBlockStateId(0, 0, 0));
		self::assertNotSame($sub1->getBlockLightArray()->get(0, 0, 0), $sub2->getBlockLightArray()->get(0, 0, 0));
		self::assertNotSame($sub1->getBlockSkyLightArray()->get(0, 0, 0), $sub2->getBlockSkyLightArray()->get(0, 0, 0));
	}

	/**
	 * Writing through one sub chunk must never leak into another that shares the same uniform light
	 */
	public function testUniformLightIsCopiedOnWrite() : void{
		$sub1 = new SubChunk(0, [], new PalettedBlockArray(BiomeIds::OCEAN));
		$sub2 = new SubChunk(0, [], new PalettedBlockArray(BiomeIds::OCEAN));
		$sub1->setUniformBlockSkyLight(15);
		$sub2->setUniformBlockSkyLight(15);
		$sub1->setUniformBlockLight(7);
		$sub2->setUniformBlockLight(7);

		self::assertSame(15, $sub1->getBlockSkyLightAt(3, 4, 5));
		self::assertSame(7, $sub2->getBlockLightAt(3, 4, 5));

		$sub1->getBlockSkyLightArray()->set(3, 4, 5, 2);
		$sub1->getBlockLightArray()->set(3, 4, 5, 1);

		self::assertSame(2, $sub1->getBlockSkyLightAt(3, 4, 5));
		self::assertSame(1, $sub1->getBlockLightAt(3, 4, 5));
		self::assertSame(15, $sub2->getBlockSkyLightAt(3, 4, 5));
		self::assertSame(7, $sub2->getBlockLightAt(3, 4, 5));

		$sub3 = clone $sub2;
		$sub3->getBlockSkyLightArray()->set(0, 0, 0, 0);
		self::assertSame(15, $sub2->getBlockSkyLightAt(0, 0, 0));
		self::assertSame(0, $sub3->getBlockSkyLightAt(0, 0, 0));

		$sub2->setUniformBlockSkyLight(0);
		self::assertSame(0, $sub2->getBlockSkyLightAt(3, 4, 5));
		self::assertTrue($sub2->getBlockSkyLightArray()->isUniform(0));
	}
}
