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
use pocketmine\event\block\BlockItemPickupEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

final class HopperPickupShulkerNestTest extends HopperTestBase implements Listener{
	private const DURATION_TICKS = 80;

	private ?Inventory $shulkerInventory = null;
	private int $calls = 0;

	public function __construct(\Logger $logger, Main $plugin){
		parent::__construct(
			$logger,
			$plugin,
			"Hopper shulker-in-shulker pickup test",
			"Checks that a redirected hopper pickup cannot insert a shulker box into another shulker box"
		);
	}

	protected function setUpArea() : void{
		$this->calls = 0;

		$shulkerPos = $this->areaPos(0, 0, 0);
		$this->world->setBlock($shulkerPos, VanillaBlocks::SHULKER_BOX(), false);
		$this->shulkerInventory = $this->getContainerInventory($shulkerPos);

		$hopperPos = $this->areaPos(2, 1, 0);
		$this->world->setBlock($hopperPos, VanillaBlocks::HOPPER()->setFacing(Facing::DOWN), false);

		$this->dropAboveHopper($hopperPos, VanillaBlocks::SHULKER_BOX()->asItem());
		$this->dropAboveHopper($hopperPos, VanillaBlocks::DIRT()->asItem()->setCount(8));

		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
	}

	private function dropAboveHopper(Vector3 $hopperPos, Item $item) : void{
		$entity = $this->world->dropItem($hopperPos->add(0.5, 1.3, 0.5), $item, new Vector3(0, 0, 0), 0);
		$entity?->setHasGravity(false);
	}

	public function onBlockItemPickup(BlockItemPickupEvent $event) : void{
		if($this->shulkerInventory === null){
			return;
		}
		$this->calls++;
		$event->setInventory($this->shulkerInventory);
	}

	protected function checkInvariants(int $tick) : void{
		if($this->shulkerInventory === null){
			return;
		}
		for($slot = 0, $size = $this->shulkerInventory->getSize(); $slot < $size; $slot++){
			$item = $this->shulkerInventory->getItem($slot);
			if($item->equals(VanillaBlocks::SHULKER_BOX()->asItem(), false, false)){
				throw new TestFailedException("A shulker box was inserted into another shulker box after $tick ticks");
			}
		}
	}

	protected function checkOutcome() : void{
		if($this->calls === 0){
			throw new TestFailedException("BlockItemPickupEvent was never called, so nothing was actually tested");
		}
		if($this->shulkerInventory === null || $this->countItems([$this->shulkerInventory]) === 0){
			throw new TestFailedException("The hopper never moved the dirt into the shulker box within " . self::DURATION_TICKS . " ticks");
		}
		if($this->countDroppedItems(static fn(Item $item) : bool => $item->equals(VanillaBlocks::SHULKER_BOX()->asItem(), false, false)) !== 1){
			throw new TestFailedException("The dropped shulker box should have stayed on the ground");
		}
	}

	protected function tearDownArea() : void{
		HandlerListManager::global()->unregisterAll($this);
	}

	protected function getDurationTicks() : int{
		return self::DURATION_TICKS;
	}
}
