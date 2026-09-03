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
use pocketmine\block\Jukebox;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\HandlerListManager;
use pocketmine\inventory\Inventory;
use pocketmine\item\Record;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

final class HopperJukeboxDupeTest extends HopperTestBase{
	private const DURATION_TICKS = 200;
	private const PUSHED_RECORDS = 4;

	private ?HostileJukeboxListener $listener = null;
	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $inventories = [];
	/**
	 * @var Vector3[]
	 * @phpstan-var list<Vector3>
	 */
	private array $jukeboxPositions = [];
	private int $initialTotal = 0;
	private Inventory $pushSource;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper jukebox dupe test",
			"Checks that hoppers neither duplicate nor destroy records when a plugin moves records in and out of jukeboxes from within InventoryMoveItemEvent"
		);
	}

	protected function setUpArea() : void{
		$this->inventories = [];
		$this->jukeboxPositions = [];

		$this->buildPushLane();
		$this->buildPullLane();

		$this->initialTotal = $this->countTotal();

		$this->listener = new HostileJukeboxListener($this->world, $this->jukeboxPositions);
		$this->plugin->getServer()->getPluginManager()->registerEvents($this->listener, $this->plugin);
	}

	private function buildPushLane() : void{
		$jukeboxPos = $this->areaPos(0, 1, 0);
		$this->world->setBlock($jukeboxPos, VanillaBlocks::JUKEBOX(), false);
		$this->world->setBlock($this->areaPos(0, 2, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$sourcePos = $this->areaPos(0, 3, 0);
		$this->world->setBlock($sourcePos, VanillaBlocks::CHEST(), false);
		$source = $this->getContainerInventory($sourcePos);
		for($slot = 0; $slot < self::PUSHED_RECORDS; $slot++){
			// records don't stack, so every record needs a slot of its own
			$source->setItem($slot, VanillaItems::RECORD_CAT());
		}

		$this->jukeboxPositions[] = $jukeboxPos;
		$this->inventories[] = $source;
		$this->inventories[] = $this->getContainerInventory($this->areaPos(0, 2, 0));
		$this->pushSource = $source;
	}

	private function buildPullLane() : void{
		$destinationPos = $this->areaPos(4, 1, 0);
		$this->world->setBlock($destinationPos, VanillaBlocks::CHEST(), false);
		$this->world->setBlock($this->areaPos(4, 2, 0), VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$jukeboxPos = $this->areaPos(4, 3, 0);
		$this->world->setBlock($jukeboxPos, VanillaBlocks::JUKEBOX(), false);
		$jukebox = $this->world->getBlock($jukeboxPos);
		if(!$jukebox instanceof Jukebox){
			throw new TestFailedException("Expected a jukebox at " . $jukeboxPos->__toString());
		}
		$jukebox->insertRecord(VanillaItems::RECORD_CAT());
		$this->world->setBlock($jukeboxPos, $jukebox, false);

		$this->jukeboxPositions[] = $jukeboxPos;
		$this->inventories[] = $this->getContainerInventory($this->areaPos(4, 2, 0));
		$this->inventories[] = $this->getContainerInventory($destinationPos);
	}

	private function countTotal() : int{
		$total = $this->countItems($this->inventories) + $this->countDroppedItems();
		foreach($this->jukeboxPositions as $position){
			$jukebox = $this->world->getBlock($position);
			if($jukebox instanceof Jukebox && $jukebox->getRecord() !== null){
				$total++;
			}
		}
		return $total;
	}

	protected function checkInvariants(int $tick) : void{
		$expected = $this->initialTotal + ($this->listener?->getLedger() ?? 0);
		$total = $this->countTotal();
		if($total !== $expected){
			throw new TestFailedException("Expected $expected items after $tick ticks, but found $total");
		}
	}

	protected function checkOutcome() : void{
		if($this->listener === null || $this->listener->getCalls() === 0){
			throw new TestFailedException("InventoryMoveItemEvent was never called, so nothing was actually tested");
		}

		$remaining = 0;
		for($slot = 0, $size = $this->pushSource->getSize(); $slot < $size; $slot++){
			if($this->pushSource->getItem($slot) instanceof Record){
				$remaining++;
			}
		}
		if($remaining >= self::PUSHED_RECORDS){
			throw new TestFailedException("The hopper didn't push a single record into the jukebox within " . self::DURATION_TICKS . " ticks");
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
