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
use pocketmine\block\VanillaBlocks;
use pocketmine\event\HandlerListManager;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;

final class HopperMoveEventDupeTest extends HopperTestBase{
	private const DURATION_TICKS = 200;

	private ?HostileMoveItemListener $listener = null;
	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $inventories = [];
	private int $initialTotal = 0;
	private Inventory $chestDestination;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper InventoryMoveItemEvent abuse test",
			"Checks that hoppers neither duplicate nor destroy items when a plugin mutates the inventories from within InventoryMoveItemEvent"
		);
	}

	protected function setUpArea() : void{
		$this->inventories = [];

		$this->chestDestination = $this->buildChestLane();
		$this->buildFurnaceLane();
		$this->buildBrewingStandLane();

		$this->initialTotal = $this->countItems($this->inventories);

		$this->listener = new HostileMoveItemListener();
		$this->plugin->getServer()->getPluginManager()->registerEvents($this->listener, $this->plugin);
	}

	private function buildChestLane() : Inventory{
		$destinationPos = $this->areaPos(0, 0, 0);
		$this->world->setBlock($destinationPos, VanillaBlocks::CHEST(), false);
		$this->world->setBlock($this->areaPos(0, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(0, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));

		$destination = $this->getContainerInventory($destinationPos);
		$this->inventories[] = $source;
		$this->inventories[] = $this->getContainerInventory($this->areaPos(0, 1, 0));
		$this->inventories[] = $destination;
		return $destination;
	}

	private function buildFurnaceLane() : void{
		$this->world->setBlock($this->areaPos(3, 0, 0), VanillaBlocks::FURNACE(), false);
		$this->world->setBlock($this->areaPos(3, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(3, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));

		$this->inventories[] = $source;
		$this->inventories[] = $this->getContainerInventory($this->areaPos(3, 1, 0));
		$this->inventories[] = $this->getContainerInventory($this->areaPos(3, 0, 0));
	}

	private function buildBrewingStandLane() : void{
		$this->world->setBlock($this->areaPos(7, 1, 0), VanillaBlocks::BREWING_STAND(), false);
		$this->world->setBlock($this->areaPos(6, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::EAST), false);

		$sourcePos = $this->areaPos(6, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$bottle = VanillaItems::GLASS_BOTTLE();
		$source->setItem(0, $bottle->setCount($bottle->getMaxStackSize()));

		$this->inventories[] = $source;
		$this->inventories[] = $this->getContainerInventory($this->areaPos(6, 1, 0));
		$this->inventories[] = $this->getContainerInventory($this->areaPos(7, 1, 0));
	}

	protected function checkInvariants(int $tick) : void{
		$expected = $this->initialTotal + ($this->listener?->getLedger() ?? 0);
		$total = $this->countItems($this->inventories);
		if($total !== $expected){
			throw new TestFailedException("Expected $expected items after $tick ticks, but found $total");
		}
	}

	protected function checkOutcome() : void{
		if($this->listener === null || $this->listener->getCalls() === 0){
			throw new TestFailedException("InventoryMoveItemEvent was never called, so nothing was actually tested");
		}
		if($this->countItems([$this->chestDestination]) === 0){
			throw new TestFailedException("The hopper didn't move a single item into the destination chest within " . self::DURATION_TICKS . " ticks");
		}
	}

	protected function tearDownArea() : void{
		if($this->listener !== null){
			HandlerListManager::global()->unregisterAll($this->listener);
			$this->listener = null;
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
