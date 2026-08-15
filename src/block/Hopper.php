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

namespace pocketmine\block;

use pocketmine\block\inventory\BrewingStandInventory;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\BrewingStand as TileBrewingStand;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Furnace as TileFurnace;
use pocketmine\block\tile\Hopper as TileHopper;
use pocketmine\block\tile\Jukebox as TileJukebox;
use pocketmine\block\utils\PoweredByRedstone;
use pocketmine\block\utils\PoweredByRedstoneTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockItemPickupEvent;
use pocketmine\event\inventory\InventoryMoveItemEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Bucket;
use pocketmine\item\GlassBottle;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\Record;
use pocketmine\item\SplashPotion;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use function min;

class Hopper extends Transparent implements PoweredByRedstone{
	use PoweredByRedstoneTrait;

	private int $facing = Facing::DOWN;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facingExcept($this->facing, Facing::UP);
		$w->bool($this->powered);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		if($facing === Facing::UP){
			throw new \InvalidArgumentException("Hopper may not face upward");
		}
		$this->facing = $facing;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$result = [
			AxisAlignedBB::one()->trim(Facing::UP, 6 / 16) //the empty area around the bottom is currently considered solid
		];

		foreach(Facing::HORIZONTAL as $f){ //add the frame parts around the bowl
			$result[] = AxisAlignedBB::one()->trim($f, 14 / 16);
		}
		return $result;
	}

	public function getSupportType(int $facing) : SupportType{
		return match($facing){
			Facing::UP => SupportType::FULL,
			Facing::DOWN => $this->facing === Facing::DOWN ? SupportType::CENTER : SupportType::NONE,
			default => SupportType::NONE
		};
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$this->facing = $face === Facing::DOWN ? Facing::DOWN : Facing::opposite($face);

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof TileHopper){ //TODO: find a way to have inventories open on click without this boilerplate in every block
				$player->setCurrentWindow($tile->getInventory());
			}
			return true;
		}
		return false;
	}

	public function onScheduledUpdate() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileHopper){
			return;
		}
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);

		$transferCooldown = $tile->getTransferCooldown();
		if($transferCooldown > 0){
			$transferCooldown--;
			$tile->setTransferCooldown($transferCooldown);
		}

		if($this->isPowered() || $transferCooldown > 0){
			return;
		}

		$inventory = $tile->getInventory();
		$success = $this->push($inventory);
		// Hoppers that have a container above them, won't try to pick up items.
		$origin = $this->position->getWorld()->getTile($this->position->getSide(Facing::UP));
		if($origin instanceof Container){
			// Hoppers only pull from the container part directly above them, not from the other half of a double chest.
			$success = $this->pull($inventory, $origin->getRealInventory()) || $success;
		}elseif($origin instanceof TileJukebox){
			$success = $this->pullFromJukebox($inventory, $origin) || $success;
		}else{
			$success = $this->pickup($inventory) || $success;
		}
		// The cooldown is only set back to the default amount of ticks if the hopper has done anything.
		if($success){
			$tile->setTransferCooldown(TileHopper::DEFAULT_TRANSFER_COOLDOWN);
		}
	}

	/**
	 * This function handles pushing items from the hopper to a tile in the direction the hopper is facing.
	 * Returns true if an item was successfully pushed or false on failure.
	 */
	private function push(HopperInventory $inventory) : bool{
		if($this->isInventoryEmpty($inventory)){
			return false;
		}
		$destination = $this->position->getWorld()->getTile($this->position->getSide($this->facing));
		if($destination === null){
			return false;
		}

		for($slot = 0; $slot < $inventory->getSize(); $slot++){
			$item = $inventory->getItem($slot);
			if($item->isNull()){
				continue;
			}

			// Hoppers interact differently when pushing into different kinds of tiles.
			//TODO: Composter
			if($destination instanceof TileFurnace){
				// If the hopper is facing down, it will push every item to the furnace's input slot, even items that aren't smeltable.
				// If the hopper is facing in any other direction, it will only push items that can be used as fuel to the furnace's fuel slot.
				if($this->facing === Facing::DOWN){
					$slotInFurnace = FurnaceInventory::SLOT_INPUT;
				}else{
					if($item->getFuelTime() === 0){
						continue;
					}
					$slotInFurnace = FurnaceInventory::SLOT_FUEL;
				}
				if(!$this->transferToSlot($inventory, $slot, $item, $destination->getInventory(), $slotInFurnace)){
					continue;
				}
				return true;

			}elseif($destination instanceof TileBrewingStand){
				$brewingInventory = $destination->getInventory();
				$slotInStand = $this->getBrewingStandSlot($brewingInventory, $item);
				if($slotInStand === null || !$this->transferToSlot($inventory, $slot, $item, $brewingInventory, $slotInStand)){
					continue;
				}
				return true;

			}elseif($destination instanceof TileHopper){
				$destinationInventory = $destination->getInventory();
				// Hoppers pushing into empty hoppers set the empty hoppers transfer cooldown back to the default amount of ticks.
				$resetDestinationCooldown = $this->isInventoryEmpty($destinationInventory);
				if(!$this->transferToInventory($inventory, $slot, $item, $destinationInventory)){
					continue;
				}
				if($resetDestinationCooldown){
					$destination->setTransferCooldown(TileHopper::DEFAULT_TRANSFER_COOLDOWN);
				}
				return true;

			}elseif($destination instanceof TileJukebox){
				if(!($item instanceof Record)){
					continue;
				}
				//TODO:
				// Jukeboxes actually emit a redstone signal when playing a record so nearby hoppers are blocked and
				// prevented from inserting another disk. Because neither does redstone work properly nor can we check if
				// a jukebox is still playing a record or has already finished it, we can just check if it has already a
				// record inserted.
				if($destination->getRecord() !== null){
					return false;
				}

				// The Jukebox block is handling the playing of records, so we need to get it here and can't use TileJukebox::setRecord().
				$jukeboxBlock = $destination->getBlock();
				if($jukeboxBlock instanceof Jukebox){
					$recordToPush = $this->callMoveItemEvent($inventory, null, $item->pop());
					if($recordToPush instanceof Record){
						$jukeboxBlock->insertRecord($recordToPush);
						$jukeboxBlock->getPosition()->getWorld()->setBlock($jukeboxBlock->getPosition(), $jukeboxBlock);
						$inventory->setItem($slot, $item);
						return true;
					}
				}
				return false;

			}elseif($destination instanceof Container){
				if(!$this->transferToInventory($inventory, $slot, $item, $destination->getInventory())){
					continue;
				}
				return true;

			}else{
				return false;
			}
		}
		return false;
	}

	/**
	 * This function handles pulling items by the hopper from a container above.
	 * Returns true if an item was successfully pulled or false on failure.
	 */
	private function pull(HopperInventory $inventory, Inventory $origin) : bool{
		// Hoppers interact differently when pulling from different kinds of tiles.
		//TODO: Composter
		if($origin instanceof FurnaceInventory){
			// Hoppers either pull empty buckets from the furnace's fuel slot or pull from its result slot.
			// They prioritise pulling from the fuel slot over the result slot.
			$item = $origin->getFuel();
			if($item instanceof Bucket){
				$slot = FurnaceInventory::SLOT_FUEL;
			}else{
				$slot = FurnaceInventory::SLOT_RESULT;
				$item = $origin->getResult();
				if($item->isNull()){
					return false;
				}
			}
			return $this->transferToInventory($origin, $slot, $item, $inventory);

		}elseif($origin instanceof BrewingStandInventory){
			// Hoppers only pull the brewed potions out of a brewing stand's bottle slots.
			foreach([BrewingStandInventory::SLOT_BOTTLE_LEFT, BrewingStandInventory::SLOT_BOTTLE_MIDDLE, BrewingStandInventory::SLOT_BOTTLE_RIGHT] as $slot){
				$item = $origin->getItem($slot);
				if($item->isNull() || !$this->transferToInventory($origin, $slot, $item, $inventory)){
					continue;
				}
				return true;
			}

		}else{
			for($slot = 0, $size = $origin->getSize(); $slot < $size; $slot++){
				$item = $origin->getItem($slot);
				if($item->isNull() || !$this->transferToInventory($origin, $slot, $item, $inventory)){
					continue;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * This function handles pulling the inserted record out of a jukebox above the hopper.
	 * Returns true if the record was successfully pulled or false on failure.
	 */
	private function pullFromJukebox(HopperInventory $inventory, TileJukebox $jukebox) : bool{
		//TODO:
		// Just like inserting a record, pulling one out should be blocked while the jukebox is playing, since a playing
		// jukebox emits a redstone signal which powers the hopper. We can neither rely on redstone nor tell whether the
		// record has finished playing, so the record is pulled out immediately for now.

		// The Jukebox block is handling the playing of records, so we need to get it here and can't use TileJukebox::setRecord().
		$jukeboxBlock = $jukebox->getBlock();
		if(!$jukeboxBlock instanceof Jukebox){
			return false;
		}
		$record = $jukeboxBlock->getRecord();
		if($record === null || !$inventory->canAddItem($record)){
			return false;
		}
		$recordToPull = $this->callMoveItemEvent(null, $inventory, $record);
		if($recordToPull === null || !$inventory->canAddItem($recordToPull)){
			return false;
		}

		$jukeboxBlock->extractRecord();
		$this->position->getWorld()->setBlock($jukeboxBlock->getPosition(), $jukeboxBlock);
		$inventory->addItem($recordToPull);
		return true;
	}

	/**
	 * This function handles picking up items by the hopper.
	 * Returns true if an item was successfully picked up or false on failure.
	 */
	private function pickup(HopperInventory $inventory) : bool{
		// In Bedrock Edition hoppers collect from the lower 3/4 of the block space above them.
		$pickupCollisionBox = new AxisAlignedBB(
			$this->position->getX(),
			$this->position->getY() + 1,
			$this->position->getZ(),
			$this->position->getX() + 1,
			$this->position->getY() + 1.75,
			$this->position->getZ() + 1
		);

		foreach($this->position->getWorld()->getNearbyEntities($pickupCollisionBox) as $entity){
			if($entity->isClosed() || $entity->isFlaggedForDespawn() || !$entity instanceof ItemEntity){
				continue;
			}
			// Unlike Java Edition, Bedrock Edition's hoppers don't save in which order item entities landed on top of them to collect them in that order.
			// In Bedrock Edition hoppers collect item entities in the order in which they entered the chunk.
			// Because of how entities are saved by PocketMine-MP the first entities of this loop are also the first ones who were saved.
			// That's why we don't need to implement any sorting mechanism.
			$item = $entity->getItem();
			if($inventory->getAddableItemQuantity($item) <= 0){
				continue;
			}

			$ev = new BlockItemPickupEvent($this, $entity, $item, $inventory);
			$ev->call();
			if($ev->isCancelled()){
				continue;
			}
			$destination = $ev->getInventory();
			if($destination === null){
				continue;
			}
			$pickedUpItem = $ev->getItem();
			// The item left on the ground is the one the entity holds, so a different item may not be picked up in its
			// place - otherwise the leftover stack would no longer match what was actually taken.
			if(!$pickedUpItem->canStackWith($item)){
				continue;
			}
			// Hoppers pick up as much of the item entity's stack as they can hold and leave the rest on the ground.
			$entityCount = $item->getCount();
			$addableQuantity = min($destination->getAddableItemQuantity($pickedUpItem), $entityCount);
			if($addableQuantity <= 0){
				continue;
			}

			$destination->addItem((clone $pickedUpItem)->setCount($addableQuantity));
			$remainingCount = $entityCount - $addableQuantity;
			if($remainingCount > 0){
				$entity->setStackSize($remainingCount);
			}else{
				$entity->flagForDespawn();
			}
			return true;
		}
		return false;
	}

	/**
	 * Moves a single item out of the given source slot into a fixed slot of the destination inventory, merging it with
	 * the item already occupying that slot. Returns true if the item was moved.
	 */
	private function transferToSlot(Inventory $source, int $sourceSlot, Item $sourceItem, Inventory $destination, int $destinationSlot) : bool{
		$itemInSlot = $destination->getItem($destinationSlot);
		if(!$itemInSlot->isNull() && (!$itemInSlot->canStackWith($sourceItem) || $itemInSlot->getCount() >= $itemInSlot->getMaxStackSize())){
			return false;
		}

		$itemToMove = $this->callMoveItemEvent($source, $destination, $sourceItem->pop());
		if($itemToMove === null || !$this->canMergeInto($destination, $itemInSlot, $itemToMove)){
			return false;
		}
		if(!$itemInSlot->isNull()){
			$itemInSlot->setCount($itemInSlot->getCount() + $itemToMove->getCount());
		}else{
			$itemInSlot = $itemToMove;
		}

		$destination->setItem($destinationSlot, $itemInSlot);
		$source->setItem($sourceSlot, $sourceItem);
		return true;
	}

	/**
	 * Moves a single item out of the given source slot into the first slot of the destination inventory that can hold
	 * it. Returns true if the item was moved.
	 */
	private function transferToInventory(Inventory $source, int $sourceSlot, Item $sourceItem, Inventory $destination) : bool{
		$itemToMove = $sourceItem->pop();
		if(!$destination->canAddItem($itemToMove)){
			return false;
		}
		$itemToMove = $this->callMoveItemEvent($source, $destination, $itemToMove);
		if($itemToMove === null || !$destination->canAddItem($itemToMove)){
			return false;
		}

		$source->setItem($sourceSlot, $sourceItem);
		$destination->addItem($itemToMove);
		return true;
	}

	/**
	 * Returns whether the given item can be merged into the item currently occupying a slot.
	 */
	private function canMergeInto(Inventory $inventory, Item $existing, Item $incoming) : bool{
		$maxStackSize = min($inventory->getMaxStackSize(), $incoming->getMaxStackSize());
		if($existing->isNull()){
			return $incoming->getCount() <= $maxStackSize;
		}
		return $existing->canStackWith($incoming) && $existing->getCount() + $incoming->getCount() <= $maxStackSize;
	}

	/**
	 * Returns whether the given inventory holds no items.
	 */
	private function isInventoryEmpty(Inventory $inventory) : bool{
		for($slot = 0, $size = $inventory->getSize(); $slot < $size; $slot++){
			if(!$inventory->isSlotEmpty($slot)){
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the item to move after the event has been called, or null if the move was cancelled or the event left
	 * nothing to move.
	 */
	private function callMoveItemEvent(?Inventory $source, ?Inventory $destination, Item $item) : ?Item{
		$ev = new InventoryMoveItemEvent($source, $destination, $item);
		$ev->call();
		if($ev->isCancelled()){
			return null;
		}
		$itemToMove = $ev->getItem();
		if($itemToMove->isNull()){
			return null;
		}
		// Only ever a single item is taken out of the source inventory, so the count has to be clamped here - otherwise
		// an event handler raising it would create items out of thin air.
		return $itemToMove->setCount(1);
	}

	/**
	 * Returns the brewing stand slot the given item would be pushed into, or null if the item cannot be pushed.
	 */
	private function getBrewingStandSlot(BrewingStandInventory $inventory, Item $item) : ?int{
		// Hoppers pushing from above fill the ingredient slot, while hoppers pushing from the side fill the fuel and bottle slots.
		if($this->facing === Facing::DOWN){
			return BrewingStandInventory::SLOT_INGREDIENT;
		}
		if($item->equals(VanillaItems::BLAZE_POWDER(), true, false)){
			return BrewingStandInventory::SLOT_FUEL;
		}
		if(!$item instanceof Potion && !$item instanceof SplashPotion && !$item instanceof GlassBottle){
			return null;
		}

		foreach([BrewingStandInventory::SLOT_BOTTLE_LEFT, BrewingStandInventory::SLOT_BOTTLE_MIDDLE, BrewingStandInventory::SLOT_BOTTLE_RIGHT] as $bottleSlot){
			$itemInSlot = $inventory->getItem($bottleSlot);
			if($itemInSlot->isNull() || ($itemInSlot->canStackWith($item) && $itemInSlot->getCount() < $itemInSlot->getMaxStackSize())){
				return $bottleSlot;
			}
		}
		return null;
	}
}
