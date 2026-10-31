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
use pocketmine\block\utils\RedstoneWireConnection;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateStringValues;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

class NeighbourConnectionNetworkTest extends TestCase{

	//what we stamped on block states before 1.26.60, ahead of Mojang's own numbering
	private const ALTAY_1_26_50 = (1 << 24) | (26 << 16) | (50 << 8);
	//1.21.60.33, which vanilla still writes in 1.26.60
	private const VANILLA = 18168865;

	public function testChorusPlantWritesAllSixConnections() : void{
		$plant = VanillaBlocks::CHORUS_PLANT()
			->setConnected(Facing::UP, true)
			->setConnected(Facing::DOWN, true)
			->setConnected(Facing::WEST, true);
		$states = $this->serialize($plant);

		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_UP));
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_DOWN));
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_WEST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_EAST));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_NORTH));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_SOUTH));
	}

	public function testFireWritesItsConnectionsNextToItsAge() : void{
		$fire = VanillaBlocks::FIRE()
			->setAge(7)
			->setConnected(Facing::UP, true)
			->setConnected(Facing::NORTH, true);
		$states = $this->serialize($fire);

		$age = $states[BlockStateNames::AGE] ?? null;
		self::assertInstanceOf(IntTag::class, $age);
		self::assertSame(7, $age->getValue());
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_UP));
		self::assertSame(1, $this->byteState($states, BlockStateNames::MC_CONNECTION_NORTH));
		self::assertSame(0, $this->byteState($states, BlockStateNames::MC_CONNECTION_SOUTH));
	}

	public function testFireRefusesToConnectDownwards() : void{
		$this->expectException(\InvalidArgumentException::class);
		VanillaBlocks::FIRE()->setConnected(Facing::DOWN, true);
	}

	public function testRedstoneWireWritesEachSide() : void{
		$wire = VanillaBlocks::REDSTONE_WIRE()
			->setOutputSignalStrength(9)
			->setConnection(Facing::NORTH, RedstoneWireConnection::UP)
			->setConnection(Facing::EAST, RedstoneWireConnection::SIDE);
		$states = $this->serialize($wire);

		self::assertSame(BlockStateStringValues::REDSTONE_NORTH_UP, $this->stringState($states, BlockStateNames::REDSTONE_NORTH));
		self::assertSame(BlockStateStringValues::REDSTONE_EAST_SIDE, $this->stringState($states, BlockStateNames::REDSTONE_EAST));
		self::assertSame(BlockStateStringValues::REDSTONE_SOUTH_NONE, $this->stringState($states, BlockStateNames::REDSTONE_SOUTH));
		self::assertSame(BlockStateStringValues::REDSTONE_WEST_NONE, $this->stringState($states, BlockStateNames::REDSTONE_WEST));
	}

	/**
	 * The four sides are packed into one number to fit the state into 11 bits, so every combination
	 * has to come back out the way it went in.
	 */
	public function testRedstoneWireConnectionsSurviveTheRoundTrip() : void{
		$cases = RedstoneWireConnection::cases();
		foreach($cases as $north){
			foreach($cases as $south){
				foreach($cases as $west){
					foreach($cases as $east){
						$wire = VanillaBlocks::REDSTONE_WIRE()
							->setOutputSignalStrength(15)
							->setConnection(Facing::NORTH, $north)
							->setConnection(Facing::SOUTH, $south)
							->setConnection(Facing::WEST, $west)
							->setConnection(Facing::EAST, $east);
						$copy = RuntimeBlockStateRegistry::getInstance()->fromStateId($wire->getStateId());

						self::assertInstanceOf(RedstoneWire::class, $copy);
						self::assertSame(15, $copy->getOutputSignalStrength());
						self::assertSame($north, $copy->getConnection(Facing::NORTH));
						self::assertSame($south, $copy->getConnection(Facing::SOUTH));
						self::assertSame($west, $copy->getConnection(Facing::WEST));
						self::assertSame($east, $copy->getConnection(Facing::EAST));
					}
				}
			}
		}
	}

	public function testChorusPlantSavedBeforeTheConnectionsUpgrades() : void{
		foreach([self::ALTAY_1_26_50, self::VANILLA] as $version){
			$block = $this->upgradeAndDeserialize(new BlockStateData(BlockTypeNames::CHORUS_PLANT, [], $version), BlockStateNames::MC_CONNECTION_DOWN);
			self::assertInstanceOf(ChorusPlant::class, $block);
			foreach(Facing::ALL as $facing){
				self::assertFalse($block->isConnected($facing));
			}
		}
	}

	public function testFireSavedBeforeTheConnectionsKeepsItsAge() : void{
		foreach([self::ALTAY_1_26_50, self::VANILLA] as $version){
			$block = $this->upgradeAndDeserialize(new BlockStateData(BlockTypeNames::FIRE, [
				BlockStateNames::AGE => new IntTag(12)
			], $version), BlockStateNames::MC_CONNECTION_UP);
			self::assertInstanceOf(Fire::class, $block);
			self::assertSame(12, $block->getAge());
			self::assertFalse($block->isConnected(Facing::UP));
		}
	}

	public function testRedstoneWireSavedBeforeTheConnectionsKeepsItsSignal() : void{
		foreach([self::ALTAY_1_26_50, self::VANILLA] as $version){
			$block = $this->upgradeAndDeserialize(new BlockStateData(BlockTypeNames::REDSTONE_WIRE, [
				BlockStateNames::REDSTONE_SIGNAL => new IntTag(4)
			], $version), BlockStateNames::REDSTONE_EAST);
			self::assertInstanceOf(RedstoneWire::class, $block);
			self::assertSame(4, $block->getOutputSignalStrength());
			foreach(Facing::HORIZONTAL as $facing){
				self::assertSame(RedstoneWireConnection::NONE, $block->getConnection($facing));
			}
		}
	}

	private function upgradeAndDeserialize(BlockStateData $data, string $addedState) : Block{
		$upgraded = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($data);
		//the deserializer would quietly fill a missing state in, so check the upgrade itself put it there
		self::assertNotNull($upgraded->getState($addedState));
		return RuntimeBlockStateRegistry::getInstance()->fromStateId(
			GlobalBlockStateHandlers::getDeserializer()->deserialize($upgraded)
		);
	}

	/**
	 * @return Tag[]
	 * @phpstan-return array<string, Tag>
	 */
	private function serialize(Block $block) : array{
		return GlobalBlockStateHandlers::getSerializer()->serialize($block->getStateId())->getStates();
	}

	/**
	 * @param Tag[] $states
	 * @phpstan-param array<string, Tag> $states
	 */
	private function byteState(array $states, string $name) : int{
		$tag = $states[$name] ?? null;
		self::assertInstanceOf(ByteTag::class, $tag);
		return $tag->getValue();
	}

	/**
	 * @param Tag[] $states
	 * @phpstan-param array<string, Tag> $states
	 */
	private function stringState(array $states, string $name) : string{
		$tag = $states[$name] ?? null;
		self::assertInstanceOf(StringTag::class, $tag);
		return $tag->getValue();
	}
}
