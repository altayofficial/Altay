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
use pocketmine\event\inventory\InventoryMoveItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;
use function spl_object_id;

final class HostileMoveItemListener implements Listener{
	private const BEHAVIOUR_COUNT = 6;

	private int $calls = 0;
	private int $ledger = 0;
	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $callsPerDestination = [];

	public function onInventoryMoveItem(InventoryMoveItemEvent $event) : void{
		$this->calls++;

		$destination = $event->getDestination();
		$key = $destination === null ? 0 : spl_object_id($destination);
		$behaviour = ($this->callsPerDestination[$key] = ($this->callsPerDestination[$key] ?? 0) + 1) - 1;

		switch($behaviour % self::BEHAVIOUR_COUNT){
			case 0:
				$event->cancel();
				break;
			case 1:
				// hoppers move a single item at a time, so raising the count must not make them move more than that.
				$item = $event->getItem();
				$event->setItem($item->setCount($item->getMaxStackSize()));
				break;
			case 2:
				// swapping the moved item for a different type is allowed, but must never change how many items exist.
				$event->setItem(VanillaBlocks::DIRT()->asItem());
				break;
			case 3:
				$this->takeFromSource($event);
				break;
			case 4:
				$this->fillDestination($event);
				break;
			case 5:
				$event->setItem(VanillaItems::AIR());
				break;
		}
	}

	private function takeFromSource(InventoryMoveItemEvent $event) : void{
		$source = $event->getSource();
		if($source === null){
			return;
		}
		for($slot = 0, $size = $source->getSize(); $slot < $size; $slot++){
			$item = $source->getItem($slot);
			if($item->isNull()){
				continue;
			}
			$item->pop();
			$source->setItem($slot, $item);
			$this->ledger--;
			return;
		}
	}

	private function fillDestination(InventoryMoveItemEvent $event) : void{
		$destination = $event->getDestination();
		if($destination === null){
			return;
		}
		$extra = VanillaBlocks::COBBLESTONE()->asItem()->setCount(1);
		$leftover = 0;
		foreach($destination->addItem($extra) as $item){
			$leftover += $item->getCount();
		}
		$this->ledger += $extra->getCount() - $leftover;
	}

	public function getLedger() : int{
		return $this->ledger;
	}

	public function getCalls() : int{
		return $this->calls;
	}
}
