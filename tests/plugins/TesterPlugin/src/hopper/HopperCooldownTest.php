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

final class HopperCooldownTest extends HopperTestBase{
	private const DURATION_TICKS = 96;
	private const TOLERANCE_TICKS = 2;
	private const LOCKED_COOLDOWN = 5;

	private Inventory $source;
	private Inventory $lockedSource;
	private Inventory $lockedDestination;
	private TileHopper $lockedTile;
	private int $initialSourceCount = 0;
	private int $initialLockedSourceCount = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper transfer cooldown test",
			"Checks that hoppers move at most one item per " . TileHopper::DEFAULT_TRANSFER_COOLDOWN . " ticks and that powered hoppers are locked"
		);
	}

	protected function setUpArea() : void{
		$this->source = $this->buildLane(0, false);
		$this->initialSourceCount = $this->countItems([$this->source]);

		$this->lockedSource = $this->buildLane(3, true);
		$this->initialLockedSourceCount = $this->countItems([$this->lockedSource]);
		$this->lockedDestination = $this->getContainerInventory($this->areaPos(3, 0, 0));

		$tile = $this->world->getTile($this->areaPos(3, 1, 0));
		if(!$tile instanceof TileHopper){
			throw new TestFailedException("Expected a hopper tile at " . $this->areaPos(3, 1, 0)->__toString());
		}
		$this->lockedTile = $tile;
		$this->lockedTile->setTransferCooldown(self::LOCKED_COOLDOWN);
	}

	private function buildLane(int $x, bool $powered) : Inventory{
		$this->world->setBlock($this->areaPos($x, 0, 0), VanillaBlocks::CHEST(), false);
		$this->world->setBlock($this->areaPos($x, 1, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN)->setPowered($powered), false);

		$sourcePos = $this->areaPos($x, 2, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		$cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
		$source->setItem(0, $cobblestone->setCount($cobblestone->getMaxStackSize()));

		return $source;
	}

	protected function checkInvariants(int $tick) : void{
		$moved = $this->initialSourceCount - $this->countItems([$this->source]);
		$maximum = intdiv($tick, TileHopper::DEFAULT_TRANSFER_COOLDOWN) + self::TOLERANCE_TICKS;
		if($moved > $maximum){
			throw new TestFailedException("The hopper moved $moved items within $tick ticks, which is more than the $maximum items its transfer cooldown allows");
		}

		if($this->countItems([$this->lockedSource]) !== $this->initialLockedSourceCount || $this->countItems([$this->lockedDestination]) !== 0){
			throw new TestFailedException("The powered hopper moved items after $tick ticks even though it is locked");
		}
		if($this->lockedTile->getTransferCooldown() !== self::LOCKED_COOLDOWN){
			throw new TestFailedException("The powered hopper ticked its transfer cooldown down to " . $this->lockedTile->getTransferCooldown() . " after $tick ticks even though it is locked");
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
