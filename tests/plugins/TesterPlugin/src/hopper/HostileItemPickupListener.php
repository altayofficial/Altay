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

use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\Inventory;
use function count;

/**
 * Abuses BlockItemPickupEvent while a hopper is collecting an item entity, by cancelling the pickup, replacing the
 * collected item and redirecting the pickup into a completely different inventory.
 */
final class HostileItemPickupListener implements Listener{
	private const BEHAVIOUR_COUNT = 6;

	private int $calls = 0;

	/**
	 * @param Inventory[] $foreignInventories inventories the pickup may be redirected into
	 * @phpstan-param list<Inventory> $foreignInventories
	 */
	public function __construct(private array $foreignInventories){}

	public function onBlockItemPickup(BlockItemPickupEvent $event) : void{
		switch($this->calls++ % self::BEHAVIOUR_COUNT){
			case 0:
				break;
			case 1:
				$event->cancel();
				break;
			case 2:
				// The stack left on the ground is what the entity holds, so raising the count must not make the hopper
				// collect more than that.
				$item = $event->getItem();
				$event->setItem($item->setCount($item->getMaxStackSize()));
				break;
			case 3:
				// Collecting a different item than the entity holds would leave a mismatching stack behind, so the
				// hopper has to skip the entity instead.
				$event->setItem(VanillaBlocks::DIRT()->asItem());
				break;
			case 4:
				$event->setInventory(null);
				break;
			case 5:
				if(count($this->foreignInventories) > 0){
					$event->setInventory($this->foreignInventories[$this->calls % count($this->foreignInventories)]);
				}
				break;
		}
	}

	public function getCalls() : int{
		return $this->calls;
	}
}
