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
use pocketmine\math\Vector3;

final class HopperPickupBoundsTest extends HopperTestBase{
	private const DURATION_TICKS = 60;
	private const STACK_SIZE = 4;
	private const DISTANCES = [1, 2];

	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $inventories = [];
	private int $outOfRangeCount = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper pickup bounds test",
			"Checks that a hopper doesn't reach items lying on the floor next to it instead of inside its own column"
		);
	}

	protected function setUpArea() : void{
		$this->inventories = [];
		$this->outOfRangeCount = 0;

		$hopperPos = $this->areaPos(8, 1, 8);
		$this->world->setBlock($this->areaPos(8, 0, 8), VanillaBlocks::CHEST(), false);
		$this->world->setBlock($hopperPos, VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);
		$this->inventories[] = $this->getContainerInventory($hopperPos);
		$this->inventories[] = $this->getContainerInventory($this->areaPos(8, 0, 8));

		// a floor around the hopper for the surrounding items to lie on, at the same height as the hopper itself.
		$stone = VanillaBlocks::STONE();
		foreach(Facing::HORIZONTAL as $facing){
			$offset = Facing::OFFSET[$facing];
			foreach(self::DISTANCES as $distance){
				$this->world->setBlock($this->areaPos(8 + $offset[0] * $distance, 1, 8 + $offset[2] * $distance), $stone, false);
			}
		}

		// the items rest on top of that floor, which reaches into the height range the hopper collects from - only
		// their distance keeps them out of its reach.
		foreach(Facing::HORIZONTAL as $facing){
			$offset = Facing::OFFSET[$facing];
			foreach(self::DISTANCES as $distance){
				$position = $this->areaPos(8 + $offset[0] * $distance, 2, 8 + $offset[2] * $distance);
				$this->dropStack($position);
				$this->outOfRangeCount += self::STACK_SIZE;
			}
		}

		// a control item inside the hopper itself, so a hopper which collects nothing at all can't pass this test.
		$this->dropStack($hopperPos->add(0, 1, 0));
	}

	private function dropStack(Vector3 $position) : void{
		$item = VanillaBlocks::COBBLESTONE()->asItem()->setCount(self::STACK_SIZE);
		$this->world->dropItem($position->add(0.5, 0, 0.5), $item, new Vector3(0, 0, 0), 0);
	}

	protected function checkInvariants(int $tick) : void{
		$collected = $this->countItems($this->inventories);
		if($collected > self::STACK_SIZE){
			throw new TestFailedException("The hopper collected $collected items after $tick ticks, which is more than the " . self::STACK_SIZE . " items lying inside it");
		}
		if($this->countDroppedItems() < $this->outOfRangeCount){
			throw new TestFailedException("Items lying next to the hopper went missing after $tick ticks");
		}
	}

	protected function checkOutcome() : void{
		if($this->countItems($this->inventories) !== self::STACK_SIZE){
			throw new TestFailedException("The hopper didn't collect the " . self::STACK_SIZE . " items lying inside it within " . self::DURATION_TICKS . " ticks");
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
