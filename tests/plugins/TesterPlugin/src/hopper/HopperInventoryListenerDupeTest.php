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
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Facing;

final class HopperInventoryListenerDupeTest extends HopperTestBase{
	private const DURATION_TICKS = 200;

	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $inventories = [];
	private int $initialTotal = 0;
	private int $ledger = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper InventoryListener destination fill test",
			"Checks that hoppers neither duplicate nor destroy items when a source InventoryListener fills the destination during setItem()"
		);
	}

	protected function setUpArea() : void{
		$this->inventories = [];
		$this->ledger = 0;

		$this->buildChestLane();
		$this->buildFurnaceLane();
		$this->initialTotal = $this->countItems($this->inventories);
	}

	private function buildChestLane() : void{
		$destinationPos = $this->areaPos(0, 0, 0);
		$this->world->setBlock($destinationPos, VanillaBlocks::CHEST(), false);
		$this->world->setBlock($this->areaPos(0, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(0, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));

		$hopper = $this->getContainerInventory($this->areaPos(0, 1, 0));
		$destination = $this->getContainerInventory($destinationPos);
		$this->attachFiller($source, $hopper);
		$this->attachFiller($hopper, $destination);
		$this->inventories[] = $source;
		$this->inventories[] = $hopper;
		$this->inventories[] = $destination;
	}

	private function buildFurnaceLane() : void{
		$destinationPos = $this->areaPos(3, 0, 0);
		$this->world->setBlock($destinationPos, VanillaBlocks::FURNACE(), false);
		$this->world->setBlock($this->areaPos(3, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(3, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));

		$hopper = $this->getContainerInventory($this->areaPos(3, 1, 0));
		$destination = $this->getContainerInventory($destinationPos);
		$this->attachFiller($source, $hopper);
		$this->attachFiller($hopper, $destination);
		$this->inventories[] = $source;
		$this->inventories[] = $hopper;
		$this->inventories[] = $destination;
	}

	private function attachFiller(Inventory $source, Inventory $destination) : void{
		$source->getListeners()->add(new CallbackInventoryListener(
			function(Inventory $inventory, int $slot, Item $oldItem) use ($destination) : void{
				$extra = VanillaBlocks::COBBLESTONE()->asItem()->setCount(1);
				$leftover = 0;
				foreach($destination->addItem($extra) as $item){
					$leftover += $item->getCount();
				}
				$this->ledger += $extra->getCount() - $leftover;
			},
			null
		));
	}

	protected function checkInvariants(int $tick) : void{
		$expected = $this->initialTotal + $this->ledger;
		$total = $this->countItems($this->inventories);
		if($total !== $expected){
			throw new TestFailedException("Expected $expected items after $tick ticks, but found $total");
		}
	}

	protected function checkOutcome() : void{
		if($this->ledger === 0){
			throw new TestFailedException("The source InventoryListener never filled the destination, so nothing was actually tested");
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
