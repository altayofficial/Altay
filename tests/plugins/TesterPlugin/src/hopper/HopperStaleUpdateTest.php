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
use pocketmine\inventory\Inventory;
use pocketmine\math\Facing;
use function abs;
use function intdiv;

final class HopperStaleUpdateTest extends HopperTestBase{
	private const DURATION_TICKS = 96;
	private const TOLERANCE_TICKS = 2;
	private const STALE_DELAY_TICKS = 40;

	private Inventory $source;
	private int $initialSourceCount = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper stale delayed-update test",
			"Checks that a leftover delayed update from a replaced block does not start a second hopper loop"
		);
	}

	protected function setUpArea() : void{
		$hopperPos = $this->areaPos(0, 1, 0);
		$this->world->setBlock($this->areaPos(0, 0, 0), VanillaBlocks::CHEST(), false);
		$this->world->scheduleDelayedBlockUpdate($hopperPos, self::STALE_DELAY_TICKS);
		$this->world->setBlock($hopperPos, VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(0, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$this->source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$this->source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));
		$this->initialSourceCount = $this->countItems([$this->source]);
	}

	protected function checkInvariants(int $tick) : void{
		$moved = $this->initialSourceCount - $this->countItems([$this->source]);
		$maximum = intdiv($tick, TileHopper::DEFAULT_TRANSFER_COOLDOWN) + self::TOLERANCE_TICKS;
		if($moved > $maximum){
			throw new TestFailedException("The hopper moved $moved items within $tick ticks, which is more than the $maximum items its transfer cooldown allows");
		}
	}

	protected function checkOutcome() : void{
		$moved = $this->initialSourceCount - $this->countItems([$this->source]);
		$expected = intdiv(self::DURATION_TICKS, TileHopper::DEFAULT_TRANSFER_COOLDOWN);
		if(abs($moved - $expected) > self::TOLERANCE_TICKS){
			throw new TestFailedException("The hopper moved $moved items within " . self::DURATION_TICKS . " ticks, but around $expected were expected");
		}
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
