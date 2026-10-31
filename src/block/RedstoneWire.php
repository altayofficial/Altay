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

use pocketmine\block\utils\AnalogRedstoneSignalEmitter;
use pocketmine\block\utils\AnalogRedstoneSignalEmitterTrait;
use pocketmine\block\utils\RedstoneWireConnection;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use function array_reverse;
use function intdiv;

class RedstoneWire extends Flowable implements AnalogRedstoneSignalEmitter, StateDeriving{
	use AnalogRedstoneSignalEmitterTrait;
	use StaticSupportTrait;

	private const CONNECTIONS = [RedstoneWireConnection::NONE, RedstoneWireConnection::SIDE, RedstoneWireConnection::UP];

	/**
	 * @var RedstoneWireConnection[]
	 * @phpstan-var array<int, RedstoneWireConnection>
	 */
	protected array $connections = [
		Facing::NORTH => RedstoneWireConnection::NONE,
		Facing::SOUTH => RedstoneWireConnection::NONE,
		Facing::WEST => RedstoneWireConnection::NONE,
		Facing::EAST => RedstoneWireConnection::NONE
	];

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, 15, $this->signalStrength);
		//four 2-bit enums would push the state past 11 bits, but there are only 3^4 combinations, which fit in 7
		$packed = 0;
		foreach(array_reverse(Facing::HORIZONTAL) as $facing){
			$packed = $packed * 3 + self::connectionIndex($this->connections[$facing]);
		}
		$w->boundedIntAuto(0, 80, $packed);
		foreach(Facing::HORIZONTAL as $facing){
			$this->connections[$facing] = self::CONNECTIONS[$packed % 3];
			$packed = intdiv($packed, 3);
		}
	}

	private static function connectionIndex(RedstoneWireConnection $connection) : int{
		return match($connection){
			RedstoneWireConnection::NONE => 0,
			RedstoneWireConnection::SIDE => 1,
			RedstoneWireConnection::UP => 2
		};
	}

	public function getConnection(int $facing) : RedstoneWireConnection{
		return $this->connections[$facing] ?? throw new \InvalidArgumentException("Facing must be horizontal");
	}

	/** @return $this */
	public function setConnection(int $facing, RedstoneWireConnection $connection) : self{
		if(!isset($this->connections[$facing])){
			throw new \InvalidArgumentException("Facing must be horizontal");
		}
		$this->connections[$facing] = $connection;
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		if(!$this->canBeSupportedAt($this)){
			$world->useBreakOn($this->position);
		}elseif($this->deriveStateFromWorld()){
			$world->setBlock($this->position, $this);
		}
	}

	public function deriveStateFromWorld() : bool{
		//a solid block on top cuts off the wire climbing up to its neighbours
		$canClimb = !self::isConductor($this->getSide(Facing::UP));

		$changed = false;
		foreach(Facing::HORIZONTAL as $facing){
			$connection = $this->findConnection($facing, $canClimb);
			if($connection !== $this->connections[$facing]){
				$this->connections[$facing] = $connection;
				$changed = true;
			}
		}
		return $changed;
	}

	private function findConnection(int $facing, bool $canClimb) : RedstoneWireConnection{
		$side = $this->getSide($facing);
		if($canClimb && $side->getSupportType(Facing::UP) === SupportType::FULL && $side->getSide(Facing::UP) instanceof RedstoneWire){
			return RedstoneWireConnection::UP;
		}
		if(self::connectsTo($side, $facing)){
			return RedstoneWireConnection::SIDE;
		}
		//wire running down the side of this block to the one below
		if(!self::isConductor($side) && $side->getSide(Facing::DOWN) instanceof RedstoneWire){
			return RedstoneWireConnection::SIDE;
		}
		return RedstoneWireConnection::NONE;
	}

	private function canBeSupportedAt(Block $block) : bool{
		return $block->getAdjacentSupportType(Facing::DOWN)->hasCenterSupport();
	}

	private static function isConductor(Block $block) : bool{
		return $block->isSolid() && !$block->isTransparent();
	}

	private static function connectsTo(Block $block, int $facing) : bool{
		return match(true){
			//a repeater only takes and gives a signal along the line it faces
			$block instanceof RedstoneRepeater => Facing::axis($block->getFacing()) === Facing::axis($facing),
			$block instanceof RedstoneWire,
			$block instanceof RedstoneComparator,
			$block instanceof RedstoneTorch,
			$block instanceof Redstone,
			$block instanceof Lever,
			$block instanceof Button,
			$block instanceof PressurePlate,
			$block instanceof DaylightSensor,
			$block instanceof DetectorRail,
			$block instanceof TripwireHook,
			$block instanceof TrappedChest,
			$block instanceof Lectern,
			$block instanceof LightningRod => true,
			default => false
		};
	}

	public function asItem() : Item{
		return VanillaItems::REDSTONE_DUST();
	}
}
