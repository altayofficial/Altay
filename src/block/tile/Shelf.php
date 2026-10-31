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

namespace pocketmine\block\tile;

use pocketmine\block\Shelf as BlockShelf;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\UnexpectedTagTypeException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\world\World;

class Shelf extends Spawnable implements Container{
	use ContainerTrait;

	private SimpleInventory $inventory;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$this->inventory = new SimpleInventory(BlockShelf::SLOT_COUNT);
		$this->inventory->getListeners()->add(CallbackInventoryListener::onAnyChange(
			static function(Inventory $unused) use ($world, $pos) : void{
				//the items are drawn from the tile data, so poke the block to get it resent
				$block = $world->getBlock($pos);
				if($block instanceof BlockShelf){
					$world->setBlock($pos, $block);
				}
			})
		);
	}

	public function getInventory() : SimpleInventory{
		return $this->inventory;
	}

	public function getRealInventory() : SimpleInventory{
		return $this->inventory;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		try{
			$itemsTag = $nbt->getListTag(Container::TAG_ITEMS, CompoundTag::class);
		}catch(UnexpectedTagTypeException){
			$itemsTag = null;
		}
		if($itemsTag === null){
			return;
		}

		$listeners = $this->inventory->getListeners()->toArray();
		$this->inventory->getListeners()->remove(...$listeners); //this prevents any events being fired by initialization

		//ok so vanilla writes one entry per slot in order, empty ones included, so the list index is the slot
		$newContents = [];
		foreach($itemsTag as $slot => $itemNBT){
			if($slot >= BlockShelf::SLOT_COUNT){
				break;
			}
			if($itemNBT->getByte(SavedItemStackData::TAG_COUNT, 0) === 0){
				continue;
			}
			$newContents[$slot] = Item::safeNbtDeserialize($itemNBT, "Shelf ($this->position) slot $slot");
		}
		$this->inventory->setContents($newContents);

		$this->inventory->getListeners()->add(...$listeners);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setTag(Container::TAG_ITEMS, $this->buildItemList(fn(Item $item) => $item->nbtSerialize()));
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$translator = TypeConverter::getInstance()->getItemTranslator();
		$nbt->setTag(Container::TAG_ITEMS, $this->buildItemList(fn(Item $item) => $translator->toNetworkNbt($item)));
	}

	/**
	 * Builds the positional Items list, one compound per slot, empty slots as zero-count placeholders.
	 *
	 * @phpstan-param \Closure(Item) : CompoundTag $serialize
	 * @phpstan-return ListTag<CompoundTag>
	 */
	private function buildItemList(\Closure $serialize) : ListTag{
		$items = [];
		for($slot = 0; $slot < BlockShelf::SLOT_COUNT; $slot++){
			$item = $this->inventory->getItem($slot);
			$tag = $item->isNull() ?
				CompoundTag::create()
					->setByte(SavedItemStackData::TAG_COUNT, 0)
					->setShort(SavedItemData::TAG_DAMAGE, 0)
					->setString(SavedItemData::TAG_NAME, "")
					->setByte(SavedItemStackData::TAG_WAS_PICKED_UP, 0) :
				$serialize($item);
			$items[] = $tag->setByte(SavedItemStackData::TAG_SLOT, $slot);
		}
		return new ListTag($items, NBT::TAG_Compound);
	}
}
