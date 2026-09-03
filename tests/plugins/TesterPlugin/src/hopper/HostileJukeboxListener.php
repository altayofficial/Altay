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

use pocketmine\block\Jukebox;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\inventory\InventoryMoveItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class HostileJukeboxListener implements Listener{
	private const BEHAVIOUR_COUNT = 4;

	private int $calls = 0;
	private int $pullCalls = 0;
	private int $pushCalls = 0;
	private int $otherCalls = 0;
	private int $ledger = 0;

	/**
	 * @param Vector3[] $jukeboxPositions
	 * @phpstan-param list<Vector3> $jukeboxPositions
	 */
	public function __construct(
		private World $world,
		private array $jukeboxPositions
	){}

	public function onInventoryMoveItem(InventoryMoveItemEvent $event) : void{
		$this->calls++;

		// which side of the move is missing tells apart the three kinds of move happening in this setup, so every kind
		// gets hit by the behaviour that actually stresses it instead of relying on a single counter to line up.
		if($event->getSource() === null){
			$this->abusePullFromJukebox($event);
		}elseif($event->getDestination() === null){
			$this->abusePushIntoJukebox($event);
		}else{
			$this->abuseInventoryMove($event);
		}
	}

	private function abusePullFromJukebox(InventoryMoveItemEvent $event) : void{
		switch($this->pullCalls++ % self::BEHAVIOUR_COUNT){
			case 0:
				// the record the hopper decided to pull is dropped on the ground, so pulling it out anyway would
				// duplicate it.
				$this->ejectRecords();
				break;
			case 1:
				// only records can leave a jukebox, so this move has to be rejected rather than turning the record into
				// something else.
				$event->setItem(VanillaBlocks::DIRT()->asItem());
				break;
			default:
				break;
		}
	}

	private function abusePushIntoJukebox(InventoryMoveItemEvent $event) : void{
		switch($this->pushCalls++ % self::BEHAVIOUR_COUNT){
			case 0:
				// the jukebox the hopper decided to push into is filled, so pushing into it anyway would destroy one of
				// the two records.
				$this->insertRecords();
				break;
			case 1:
				// hoppers move a single record at a time, so raising the count must not make them move more than that.
				$item = $event->getItem();
				$event->setItem($item->setCount($item->getMaxStackSize()));
				break;
			case 2:
				$event->setItem(VanillaBlocks::DIRT()->asItem());
				break;
			default:
				break;
		}
	}

	private function abuseInventoryMove(InventoryMoveItemEvent $event) : void{
		switch($this->otherCalls++ % self::BEHAVIOUR_COUNT){
			case 0:
				$event->cancel();
				break;
			case 1:
				$this->ejectRecords();
				break;
			case 2:
				$this->insertRecords();
				break;
			default:
				break;
		}
	}

	private function ejectRecords() : void{
		foreach($this->jukeboxPositions as $position){
			$block = $this->world->getBlock($position);
			if(!$block instanceof Jukebox || $block->getRecord() === null){
				continue;
			}
			$block->ejectRecord();
			$this->world->setBlock($position, $block);
		}
	}

	private function insertRecords() : void{
		foreach($this->jukeboxPositions as $position){
			$block = $this->world->getBlock($position);
			if(!$block instanceof Jukebox || $block->getRecord() !== null){
				continue;
			}
			$block->insertRecord(VanillaItems::RECORD_CAT());
			$this->world->setBlock($position, $block);
			$this->ledger++;
		}
	}

	public function getLedger() : int{
		return $this->ledger;
	}

	public function getCalls() : int{
		return $this->calls;
	}
}
