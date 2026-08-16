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
use pocketmine\inventory\Inventory;
use pocketmine\math\Facing;

final class HopperChainStressTest extends HopperTestBase{
	private const LANES = 8;
	private const CHAIN_LENGTH = 8;
	private const DURATION_TICKS = 200;

	/**
	 * @var Inventory[][]
	 * @phpstan-var list<list<Inventory>>
	 */
	private array $laneInventories = [];
	/**
	 * @var int[]
	 * @phpstan-var list<int>
	 */
	private array $laneTotals = [];
	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $laneDestinations = [];

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper chain stress test",
			"Runs " . self::LANES . " chains of " . self::CHAIN_LENGTH . " hoppers moving full chests and checks that no items are duplicated or lost"
		);
	}

	protected function setUpArea() : void{
		$hopper = VanillaBlocks::HOPPER()->setFacing(Facing::DOWN);
		$chest = VanillaBlocks::CHEST();

		for($lane = 0; $lane < self::LANES; $lane++){
			// the lanes are two blocks apart so the chests don't pair up into double chests
			$x = $lane * 2;

			$destinationPos = $this->areaPos($x, 0, 0);
			$this->world->setBlock($destinationPos, $chest, false);
			for($y = 1; $y <= self::CHAIN_LENGTH; $y++){
				$this->world->setBlock($this->areaPos($x, $y, 0), $hopper, false);
			}
			$sourcePos = $this->areaPos($x, self::CHAIN_LENGTH + 1, 0);
			$this->world->setBlock($sourcePos, $chest, false);

			$source = $this->getContainerInventory($sourcePos);
			for($slot = 0, $size = $source->getSize(); $slot < $size; $slot++){
				// two item types are used so the hoppers have to merge into partially filled slots as well
				$item = $slot % 2 === 0 ? VanillaBlocks::COBBLESTONE()->asItem() : VanillaBlocks::DIRT()->asItem();
				$source->setItem($slot, $item->setCount($item->getMaxStackSize()));
			}

			$destination = $this->getContainerInventory($destinationPos);
			$inventories = [$source];
			for($y = 1; $y <= self::CHAIN_LENGTH; $y++){
				$inventories[] = $this->getContainerInventory($this->areaPos($x, $y, 0));
			}
			$inventories[] = $destination;

			$this->laneInventories[] = $inventories;
			$this->laneTotals[] = $this->countItems($inventories);
			$this->laneDestinations[] = $destination;
		}
	}

	protected function checkInvariants(int $tick) : void{
		foreach($this->laneInventories as $lane => $inventories){
			$total = $this->countItems($inventories);
			if($total !== $this->laneTotals[$lane]){
				throw new TestFailedException("Lane $lane held " . $this->laneTotals[$lane] . " items but holds $total after $tick ticks");
			}
		}
	}

	protected function checkOutcome() : void{
		foreach($this->laneDestinations as $lane => $destination){
			if($this->countItems([$destination]) === 0){
				throw new TestFailedException("Lane $lane didn't deliver a single item within " . self::DURATION_TICKS . " ticks");
			}
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
