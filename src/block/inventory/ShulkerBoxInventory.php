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

namespace pocketmine\block\inventory;

use pocketmine\block\BlockTypeIds;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\world\Position;
use pocketmine\world\sound\ShulkerBoxCloseSound;
use pocketmine\world\sound\ShulkerBoxOpenSound;
use pocketmine\world\sound\Sound;

class ShulkerBoxInventory extends SimpleInventory implements BlockInventory{
	use AnimatedBlockInventoryTrait;

	public function __construct(Position $holder){
		$this->holder = $holder;
		parent::__construct(27);
	}

	protected function getOpenSound() : Sound{
		return new ShulkerBoxOpenSound();
	}

	protected function getCloseSound() : Sound{
		return new ShulkerBoxCloseSound();
	}

	public function canAddItem(Item $item) : bool{
		if($this->isNestedShulkerBox($item)){
			return false;
		}
		return parent::canAddItem($item);
	}

	public function getAddableItemQuantity(Item $item) : int{
		if($this->isNestedShulkerBox($item)){
			return 0;
		}
		return parent::getAddableItemQuantity($item);
	}

	public function addItem(Item ...$slots) : array{
		$accepted = [];
		$rejected = [];
		foreach($slots as $slot){
			if($this->isNestedShulkerBox($slot)){
				$rejected[] = clone $slot;
				continue;
			}
			$accepted[] = $slot;
		}
		$leftover = $accepted === [] ? [] : parent::addItem(...$accepted);
		foreach($rejected as $item){
			$leftover[] = $item;
		}
		return $leftover;
	}

	private function isNestedShulkerBox(Item $item) : bool{
		$blockTypeId = ItemTypeIds::toBlockTypeId($item->getTypeId());
		return $blockTypeId === BlockTypeIds::SHULKER_BOX || $blockTypeId === BlockTypeIds::DYED_SHULKER_BOX;
	}

	protected function animateBlock(bool $isOpen) : void{
		$holder = $this->getHolder();

		//event ID is always 1 for a chest
		$holder->getWorld()->broadcastPacketToViewers($holder, BlockEventPacket::create(BlockPosition::fromVector3($holder), 1, $isOpen ? 1 : 0));
	}
}
