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

namespace pocketmine\network\mcpe\convert;

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\utils\VoxelShape;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\network\mcpe\protocol\VoxelShapesPacket;
use pocketmine\utils\Filesystem;
use function count;

class VoxelShapeDictionaryTest extends TestCase{

	private function makeDictionary() : VoxelShapeDictionary{
		return VoxelShapeDictionary::loadFromString(Filesystem::fileGetContents(BedrockDataFiles::VOXEL_SHAPES_JSON));
	}

	public function testVanillaOnly() : void{
		$packet = $this->makeDictionary()->getPacket();
		self::assertSame(0, $packet->getCustomShapeCount());
		self::assertArrayHasKey("minecraft:unit_cube", $packet->getNameMap());
		self::assertArrayHasKey("minecraft:empty", $packet->getNameMap());
	}

	public function testCustomShapesGoLast() : void{
		$dictionary = $this->makeDictionary();
		$vanillaCount = count($dictionary->getPacket()->getShapes());

		$dictionary = $this->makeDictionary();
		$dictionary->register("altay:half", VoxelShape::box(0, 0, 0, 1, 0.5, 1));
		$dictionary->register("altay:nothing", VoxelShape::empty());
		self::assertTrue($dictionary->isRegistered("altay:half"));
		self::assertTrue($dictionary->isRegistered("minecraft:unit_cube"));

		$packet = $dictionary->getPacket();
		self::assertSame(2, $packet->getCustomShapeCount());
		self::assertSame($vanillaCount, $packet->getNameMap()["altay:half"]);
		self::assertSame($vanillaCount + 1, $packet->getNameMap()["altay:nothing"]);

		$half = $packet->getShapes()[$vanillaCount];
		self::assertSame([0.0, 0.5], $half->getYCoordinates());
		self::assertSame([1], $half->getCells()->getStorage());
	}

	public function testSerializeBitOrder() : void{
		//two separate corners, so the grid ends up with a mix of solid and empty cells to check the bit order on
		$shape = VoxelShape::box(0.5, 0, 0, 1, 0.5, 0.5)->union(VoxelShape::box(0, 0.75, 0.75, 0.25, 1, 1));
		$serialized = VoxelShapeDictionary::serializeShape($shape);
		$cells = $serialized->getCells();
		$index = 0;
		$storage = $cells->getStorage();
		for($x = 0; $x < $cells->getXSize(); ++$x){
			for($y = 0; $y < $cells->getYSize(); ++$y){
				for($z = 0; $z < $cells->getZSize(); ++$z){
					$bit = ($storage[$index >> 3] >> ($index & 7)) & 1;
					self::assertSame($shape->isCellFilled($x, $y, $z), $bit === 1, "cell $x $y $z");
					++$index;
				}
			}
		}
	}

	public function testPacketRoundTrip() : void{
		$dictionary = $this->makeDictionary();
		$dictionary->register("altay:stair", VoxelShape::box(0, 0, 0, 1, 0.5, 1)->union(VoxelShape::box(0, 0.5, 0, 1, 1, 0.5)));
		$packet = $dictionary->getPacket();

		$writer = new ByteBufferWriter();
		$packet->encode($writer);
		$decoded = new VoxelShapesPacket();
		$decoded->decode(new ByteBufferReader($writer->getData()));

		self::assertSame($packet->getNameMap(), $decoded->getNameMap());
		self::assertSame(1, $decoded->getCustomShapeCount());
		self::assertEquals($packet->getShapes(), $decoded->getShapes());
	}

	public function testRegisterRejectsBadNames() : void{
		$dictionary = $this->makeDictionary();
		foreach(["minecraft:foo", "no_namespace", "Altay:Upper", ""] as $name){
			try{
				$dictionary->register($name, VoxelShape::fullCube());
				self::fail("\"$name\" should have been rejected");
			}catch(\InvalidArgumentException){
				//expected
			}
		}

		$dictionary->register("altay:cube", VoxelShape::fullCube());
		$this->expectException(\InvalidArgumentException::class);
		$dictionary->register("altay:cube", VoxelShape::fullCube());
	}

	public function testRegisterAfterPacketBuiltFails() : void{
		$dictionary = $this->makeDictionary();
		$dictionary->getPacket();
		$this->expectException(\LogicException::class);
		$dictionary->register("altay:late", VoxelShape::fullCube());
	}
}
