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

namespace pmmp\TesterPlugin\hopper;

use pmmp\TesterPlugin\Main;
use pmmp\TesterPlugin\TestFailedException;
use pocketmine\block\tile\Hopper as TileHopper;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\Inventory;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

final class HopperPickupRangeTest extends HopperTestBase{
	private const DURATION_TICKS = 100;
	private const STACK_SIZE = 8;
	private const SETTLE_TICKS = 20;
	private const THROWN_PICKUP_DELAY = 60;

	/**
	 * @var Inventory[][]
	 * @phpstan-var array<string, list<Inventory>>
	 */
	private array $lanes = [];
	private ?int $thrownCollectedTick = null;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper pickup range test",
			"Checks that a hopper collects items which came to rest on top of it, without waiting out their pickup delay"
		);
	}

	protected function shouldFreezeDroppedItems() : bool{
		return false;
	}

	protected function setUpArea() : void{
		$this->lanes = [];
		$this->thrownCollectedTick = null;

		$this->lanes["resting"] = $this->buildLane(2, 0);
		$this->lanes["thrown"] = $this->buildLane(6, self::THROWN_PICKUP_DELAY);
		$this->lanes["uncollectable"] = $this->buildLane(10, ItemEntity::NEVER_DESPAWN);
	}

	/**
	 * @return Inventory[]
	 * @phpstan-return list<Inventory>
	 */
	private function buildLane(int $x, int $pickupDelay) : array{
		$chestPos = $this->areaPos($x, 0, 4);
		$this->world->setBlock($chestPos, VanillaBlocks::CHEST(), false);
		$hopperPos = $this->areaPos($x, 1, 4);
		$this->world->setBlock($hopperPos, VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		// the hopper is held back until the item has come to rest, otherwise it would collect the item while it is
		// still falling past it and the test would say nothing about where items actually end up.
		$tile = $this->world->getTile($hopperPos);
		if(!$tile instanceof TileHopper){
			throw new TestFailedException("Expected a hopper tile at " . $hopperPos->__toString());
		}
		$tile->setTransferCooldown(self::SETTLE_TICKS);

		// the item is dropped from above the hopper without any motion of its own, so it falls straight down onto it
		// instead of being placed inside the collection area to begin with.
		$item = VanillaBlocks::COBBLESTONE()->asItem()->setCount(self::STACK_SIZE);
		$this->world->dropItem($hopperPos->add(0.5, 2, 0.5), $item, new Vector3(0, 0, 0), $pickupDelay);

		return [$this->getContainerInventory($hopperPos), $this->getContainerInventory($chestPos)];
	}

	protected function checkInvariants(int $tick) : void{
		$expected = self::STACK_SIZE * 3;
		$total = $this->countDroppedItems();
		foreach($this->lanes as $inventories){
			$total += $this->countItems($inventories);
		}
		if($total !== $expected){
			throw new TestFailedException("Dropped $expected items but found $total after $tick ticks");
		}

		$uncollectable = $this->countItems($this->lanes["uncollectable"]);
		if($uncollectable !== 0){
			throw new TestFailedException("The hopper collected $uncollectable items which are meant to be uncollectable after $tick ticks");
		}

		if($this->thrownCollectedTick === null && $this->countItems($this->lanes["thrown"]) > 0){
			$this->thrownCollectedTick = $tick;
		}
	}

	protected function checkOutcome() : void{
		$resting = $this->countItems($this->lanes["resting"]);
		if($resting !== self::STACK_SIZE){
			throw new TestFailedException("The hopper only collected $resting of the " . self::STACK_SIZE . " items resting on it within " . self::DURATION_TICKS . " ticks");
		}

		$thrown = $this->countItems($this->lanes["thrown"]);
		if($thrown !== self::STACK_SIZE){
			throw new TestFailedException("The hopper only collected $thrown of the " . self::STACK_SIZE . " items thrown onto it within " . self::DURATION_TICKS . " ticks");
		}
		if($this->thrownCollectedTick === null || $this->thrownCollectedTick >= self::THROWN_PICKUP_DELAY){
			throw new TestFailedException("The hopper waited until tick " . ($this->thrownCollectedTick ?? self::DURATION_TICKS) . " to collect a thrown item, which means it sat out its " . self::THROWN_PICKUP_DELAY . " tick pickup delay");
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
