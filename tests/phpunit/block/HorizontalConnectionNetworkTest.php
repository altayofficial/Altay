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
use pocketmine\block\utils\HorizontalFacingOption;
use pocketmine\block\utils\StairShape;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateStringValues;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\block\upgrade\HorizontalConnectionPaletteFixer;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;

class HorizontalConnectionNetworkTest extends TestCase{

	public function testConnectedFenceWritesConnectionBits() : void{
		$fence = VanillaBlocks::OAK_FENCE()
			->setConnected(Facing::EAST, true)
			->setConnected(Facing::WEST, true);
		$states = GlobalBlockStateHandlers::getSerializer()->serialize($fence->getStateId())->getStates();

		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_EAST));
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_WEST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_NORTH));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_SOUTH));
	}

	public function testDisconnectedFenceWritesZeroBits() : void{
		$states = GlobalBlockStateHandlers::getSerializer()->serialize(VanillaBlocks::OAK_FENCE()->getStateId())->getStates();

		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_EAST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_WEST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_NORTH));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_SOUTH));
	}

	public function testTripwireWritesConnectionBits() : void{
		$tripwire = VanillaBlocks::TRIPWIRE()
			->setConnection(Facing::NORTH, true)
			->setConnection(Facing::EAST, true);
		$states = GlobalBlockStateHandlers::getSerializer()->serialize($tripwire->getStateId())->getStates();

		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_NORTH));
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_EAST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_SOUTH));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_WEST));
	}

	public function testLegacyEmptyFenceDeserializes() : void{
		$data = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade(
			new BlockStateData(BlockTypeNames::SPRUCE_FENCE, [], 18168865)
		);
		self::assertInstanceOf(ByteTag::class, $data->getState(BlockStateNames::MC_CONNECTION_NORTH));
		$block = RuntimeBlockStateRegistry::getInstance()->fromStateId(
			GlobalBlockStateHandlers::getDeserializer()->deserialize($data)
		);
		self::assertInstanceOf(WoodenFence::class, $block);
		self::assertFalse($block->isConnected(Facing::NORTH));
	}

	public function testLegacyStairWithoutCornerDeserializes() : void{
		$data = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade(
			new BlockStateData(BlockTypeNames::NORMAL_STONE_STAIRS, [
				BlockStateNames::UPSIDE_DOWN_BIT => new ByteTag(1),
				BlockStateNames::WEIRDO_DIRECTION => new IntTag(0),
			], 18168865)
		);
		$corner = $data->getState(BlockStateNames::MC_CORNER);
		self::assertInstanceOf(StringTag::class, $corner);
		self::assertSame(BlockStateStringValues::MC_CORNER_NONE, $corner->getValue());
		$block = RuntimeBlockStateRegistry::getInstance()->fromStateId(
			GlobalBlockStateHandlers::getDeserializer()->deserialize($data)
		);
		self::assertInstanceOf(Stair::class, $block);
		self::assertTrue($block->isUpsideDown());
		self::assertSame(StairShape::STRAIGHT, $block->getShape());
	}

	public function testStairCornerWritesNetworkCorner() : void{
		$stair = VanillaBlocks::OAK_STAIRS()->setShape(StairShape::INNER_LEFT);
		$states = GlobalBlockStateHandlers::getSerializer()->serialize($stair->getStateId())->getStates();
		$corner = $states[BlockStateNames::MC_CORNER] ?? null;
		self::assertInstanceOf(StringTag::class, $corner);
		self::assertSame(BlockStateStringValues::MC_CORNER_INNER_LEFT, $corner->getValue());
	}

	public function testPaletteFixerJoinsPerpendicularStairs() : void{
		$east = VanillaBlocks::OAK_STAIRS()->setFacing(HorizontalFacingOption::EAST)->setShape(StairShape::STRAIGHT);
		$south = VanillaBlocks::OAK_STAIRS()->setFacing(HorizontalFacingOption::SOUTH)->setShape(StairShape::STRAIGHT);
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(5, 4, 4, $east->getStateId());
		$layer->set(4, 4, 4, $south->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertTrue(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));

		$fixedEast = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(5, 4, 4));
		self::assertInstanceOf(Stair::class, $fixedEast);
		self::assertSame(StairShape::INNER_RIGHT, $fixedEast->getShape());
	}

	public function testPaletteFixerDoesNotMakeACornerBlockedByMatchingStairs() : void{
		$east = VanillaBlocks::OAK_STAIRS()->setFacing(HorizontalFacingOption::EAST)->setShape(StairShape::STRAIGHT);
		$south = VanillaBlocks::OAK_STAIRS()->setFacing(HorizontalFacingOption::SOUTH)->setShape(StairShape::STRAIGHT);
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(5, 4, 4, $east->getStateId());
		$layer->set(4, 4, 4, $south->getStateId());
		$layer->set(5, 4, 5, $east->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertFalse(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));

		$fixedEast = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(5, 4, 4));
		self::assertInstanceOf(Stair::class, $fixedEast);
		self::assertSame(StairShape::STRAIGHT, $fixedEast->getShape());
	}

	public function testPaletteFixerConnectsAdjacentFences() : void{
		$fence = VanillaBlocks::OAK_FENCE();
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(4, 4, 4, $fence->getStateId());
		$layer->set(5, 4, 4, $fence->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertTrue(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));

		$west = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(4, 4, 4));
		$east = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(5, 4, 4));
		self::assertInstanceOf(WoodenFence::class, $west);
		self::assertInstanceOf(WoodenFence::class, $east);
		self::assertTrue($west->isConnected(Facing::EAST));
		self::assertTrue($east->isConnected(Facing::WEST));
	}

	public function testPaletteFixerConnectsAdjacentTripwire() : void{
		$tripwire = VanillaBlocks::TRIPWIRE();
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(4, 4, 4, $tripwire->getStateId());
		$layer->set(5, 4, 4, $tripwire->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertTrue(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));

		$west = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(4, 4, 4));
		$east = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(5, 4, 4));
		self::assertInstanceOf(Tripwire::class, $west);
		self::assertInstanceOf(Tripwire::class, $east);
		self::assertTrue($west->isConnectedTo(Facing::EAST));
		self::assertTrue($east->isConnectedTo(Facing::WEST));
	}

	public function testPaletteFixerConnectsFencesAcrossAChunkBorder() : void{
		$fence = VanillaBlocks::OAK_FENCE();
		$eastLayer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$eastLayer->set(15, 4, 4, $fence->getStateId());
		$eastEdge = new SubChunk(Block::EMPTY_STATE_ID, $eastLayer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		$westLayer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$westLayer->set(0, 4, 4, $fence->getStateId());
		$westEdge = new SubChunk(Block::EMPTY_STATE_ID, $westLayer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertTrue(HorizontalConnectionPaletteFixer::fix(
			[0 => $eastEdge],
			fn(int $offsetX, int $offsetZ) => $offsetX === 1 && $offsetZ === 0 ? [0 => $westEdge] : null
		));

		$block = RuntimeBlockStateRegistry::getInstance()->fromStateId($eastEdge->getBlockStateId(15, 4, 4));
		self::assertInstanceOf(WoodenFence::class, $block);
		self::assertTrue($block->isConnected(Facing::EAST));
	}

	public function testPaletteFixerLeavesABorderFenceAloneWithoutItsNeighbour() : void{
		$fence = VanillaBlocks::OAK_FENCE();
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(15, 4, 4, $fence->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertFalse(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));

		$block = RuntimeBlockStateRegistry::getInstance()->fromStateId($subChunk->getBlockStateId(15, 4, 4));
		self::assertInstanceOf(WoodenFence::class, $block);
		self::assertFalse($block->isConnected(Facing::EAST));
	}

	public function testPaletteFixerReportsNoChangeWhenTheBlocksAreAlreadyRight() : void{
		$west = VanillaBlocks::OAK_FENCE()->setConnected(Facing::EAST, true);
		$east = VanillaBlocks::OAK_FENCE()->setConnected(Facing::WEST, true);
		$layer = new PalettedBlockArray(Block::EMPTY_STATE_ID);
		$layer->set(4, 4, 4, $west->getStateId());
		$layer->set(5, 4, 4, $east->getStateId());
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, $layer, null, new PalettedBlockArray(BiomeIds::OCEAN));

		self::assertFalse(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));
	}

	public function testPaletteFixerSkipsEmptyPalettes() : void{
		$subChunk = new SubChunk(Block::EMPTY_STATE_ID, null, null, new PalettedBlockArray(BiomeIds::OCEAN));
		self::assertFalse(HorizontalConnectionPaletteFixer::fix([0 => $subChunk]));
	}

	/**
	 * @param array<string, \pocketmine\nbt\tag\Tag> $states
	 */
	private function byteState(array $states, string $name) : int{
		$tag = $states[$name] ?? null;
		self::assertInstanceOf(ByteTag::class, $tag);
		return $tag->getValue();
	}
}
