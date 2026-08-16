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
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\HandlerListManager;
use pocketmine\inventory\Inventory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

final class HopperPickupDupeTest extends HopperTestBase{
	private const DURATION_TICKS = 200;
	private const HOPPERS = 6;
	private const STACK_SIZE = 16;

	private ?HostileItemPickupListener $listener = null;
	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $inventories = [];
	/**
	 * @var Inventory[]
	 * @phpstan-var list<Inventory>
	 */
	private array $chestInventories = [];
	/**
	 * @var Vector3[]
	 * @phpstan-var list<Vector3>
	 */
	private array $hopperPositions = [];
	private int $spawnedTotal = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper item pickup dupe test",
			"Checks that hoppers collect exactly as many items as the item entities hold, even when a plugin rewrites BlockItemPickupEvent"
		);
	}

	protected function setUpArea() : void{
		$this->inventories = [];
		$this->chestInventories = [];
		$this->hopperPositions = [];
		$this->spawnedTotal = 0;

		$hopperInventories = [];
		for($index = 0; $index < self::HOPPERS; $index++){
			// the hoppers are two blocks apart so the chests below them don't pair up into double chests.
			$x = $index * 2;

			$chestPos = $this->areaPos($x, 0, 0);
			$this->world->setBlock($chestPos, VanillaBlocks::CHEST(), false);
			$hopperPos = $this->areaPos($x, 1, 0);
			$this->world->setBlock($hopperPos, VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

			$chest = $this->getContainerInventory($chestPos);
			$hopper = $this->getContainerInventory($hopperPos);
			$this->hopperPositions[] = $hopperPos;
			$this->chestInventories[] = $chest;
			$hopperInventories[] = $hopper;
			$this->inventories[] = $hopper;
			$this->inventories[] = $chest;
		}

		$this->refillEntities();

		$this->listener = new HostileItemPickupListener($hopperInventories);
		$this->plugin->getServer()->getPluginManager()->registerEvents($this->listener, $this->plugin);
	}

	private function refillEntities() : void{
		foreach($this->hopperPositions as $position){
			// the hoppers only collect from the lower part of the block space above them, so that's where the entities
			// have to sit.
			$box = new AxisAlignedBB($position->x, $position->y + 1, $position->z, $position->x + 1, $position->y + 2, $position->z + 1);
			$occupied = false;
			foreach($this->world->getNearbyEntities($box) as $entity){
				if($entity instanceof ItemEntity && !$entity->isFlaggedForDespawn()){
					$occupied = true;
					break;
				}
			}
			if($occupied){
				continue;
			}

			$item = VanillaBlocks::COBBLESTONE()->asItem()->setCount(self::STACK_SIZE);
			$entity = $this->world->dropItem($position->add(0.5, 1.3, 0.5), $item, new Vector3(0, 0, 0), 0);
			if($entity !== null){
				// without gravity the entity stays inside the hopper's collection area instead of dropping out of it
				// again after a few ticks.
				$entity->setHasGravity(false);
				$this->spawnedTotal += self::STACK_SIZE;
			}
		}
	}

	protected function checkInvariants(int $tick) : void{
		$total = $this->countItems($this->inventories) + $this->countDroppedItems();
		if($total !== $this->spawnedTotal){
			throw new TestFailedException("Dropped " . $this->spawnedTotal . " items but found $total after $tick ticks");
		}
		$this->refillEntities();
	}

	protected function checkOutcome() : void{
		if($this->listener === null || $this->listener->getCalls() === 0){
			throw new TestFailedException("BlockItemPickupEvent was never called, so nothing was actually tested");
		}
		if($this->countItems($this->chestInventories) === 0){
			throw new TestFailedException("The hoppers didn't collect a single item within " . self::DURATION_TICKS . " ticks");
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
