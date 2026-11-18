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

namespace pocketmine\item;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use pocketmine\world\sound\BundleDropContentsSound;
use function array_map;
use function count;
use function intdiv;
use function max;
use function usort;

/**
 * A bag that holds a mix of items. How much fits is limited by weight rather than by slots: a full stack of
 * anything that stacks to 64 fills it, so it holds 64 dirt, 16 ender pearls or a single sword.
 */
class Bundle extends Item{
	public const TAG_CONTENTS = "storage_item_component_content"; //TAG_List<TAG_Compound>

	public const MAX_WEIGHT = 64;

	/** A bundle inside another one weighs this much on top of whatever it's carrying. */
	private const NESTED_BUNDLE_WEIGHT = 4;

	private ?DyeColor $color = null;

	/**
	 * @var Item[]
	 * @phpstan-var list<Item>
	 */
	private array $contents = [];

	/**
	 * Returns the colour of the bundle, or null for a plain undyed one.
	 */
	public function getColor() : ?DyeColor{ return $this->color; }

	/** @return $this */
	public function setColor(?DyeColor $color) : self{
		$this->color = $color;
		return $this;
	}

	protected function describeState(RuntimeDataDescriber $w) : void{
		//hmm, the describer has no notion of an optional enum, so the colour always takes up its bits and a flag says
		//whether it counts
		$dyed = $this->color !== null;
		$w->bool($dyed);
		$color = $this->color ?? DyeColor::WHITE;
		$w->enum($color);
		$this->color = $dyed ? $color : null;
	}

	/**
	 * Returns the items in the bundle, oldest first.
	 *
	 * @return Item[]
	 * @phpstan-return list<Item>
	 */
	public function getContents() : array{
		return array_map(fn(Item $item) => clone $item, $this->contents);
	}

	/**
	 * Replaces the items in the bundle. Empty stacks are dropped and the rest are packed together in the given order.
	 *
	 * This doesn't enforce the weight limit or check whether the items are allowed in a bundle, so a plugin can
	 * overfill one on purpose. Use canStore() and getRemainingWeight() first if that matters.
	 *
	 * @param Item[] $contents
	 *
	 * @return $this
	 */
	public function setContents(array $contents) : self{
		$this->contents = [];
		foreach($contents as $item){
			if(!$item->isNull()){
				$this->contents[] = clone $item;
			}
		}
		return $this;
	}

	public function isEmpty() : bool{
		return count($this->contents) === 0;
	}

	/**
	 * Returns how much of the bundle's capacity is used up, out of MAX_WEIGHT.
	 */
	public function getWeight() : int{
		$weight = 0;
		foreach($this->contents as $item){
			$weight += self::getItemWeight($item);
		}
		return $weight;
	}

	public function getRemainingWeight() : int{
		return max(0, self::MAX_WEIGHT - $this->getWeight());
	}

	/**
	 * Returns how much of a bundle's capacity the given stack would use up.
	 */
	public static function getItemWeight(Item $item) : int{
		if($item instanceof Bundle){
			return (self::NESTED_BUNDLE_WEIGHT + $item->getWeight()) * $item->getCount();
		}
		return intdiv(self::MAX_WEIGHT, max(1, $item->getMaxStackSize())) * $item->getCount();
	}

	/**
	 * Returns whether the given item is allowed inside a bundle at all, regardless of how full the bundle is.
	 * Shulker boxes are kept out, same as they are kept out of each other.
	 */
	public static function canStore(Item $item) : bool{
		if($item->isNull()){
			return false;
		}
		$blockTypeId = ItemTypeIds::toBlockTypeId($item->getTypeId());
		return $blockTypeId !== BlockTypeIds::SHULKER_BOX && $blockTypeId !== BlockTypeIds::DYED_SHULKER_BOX;
	}

	/**
	 * Returns how many of the given item would fit in the bundle as it is right now.
	 */
	public function getAddableItemQuantity(Item $item) : int{
		if(!self::canStore($item)){
			return 0;
		}
		$single = (clone $item)->setCount(1);
		return intdiv($this->getRemainingWeight(), max(1, self::getItemWeight($single)));
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		if($this->isEmpty()){
			return ItemUseResult::NONE;
		}

		$kept = [];
		foreach($this->contents as $item){
			$ev = new PlayerDropItemEvent($player, $item);
			$ev->call();
			if($ev->isCancelled()){
				$kept[] = $item;
			}else{
				$player->dropItem($item);
			}
		}
		if(count($kept) === count($this->contents)){
			return ItemUseResult::FAIL;
		}
		$this->contents = $kept;
		$player->broadcastSound(new BundleDropContentsSound());

		return ItemUseResult::SUCCESS;
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->contents = [];
		$contentsTag = $tag->getListTag(self::TAG_CONTENTS, CompoundTag::class);
		if($contentsTag === null){
			return;
		}

		//items are saved with their position in the bundle, which isn't necessarily the order they're listed in
		$entries = $contentsTag->getValue();
		usort($entries, fn(CompoundTag $a, CompoundTag $b) => $a->getByte(SavedItemStackData::TAG_SLOT, 0) <=> $b->getByte(SavedItemStackData::TAG_SLOT, 0));
		foreach($entries as $entry){
			//a broken item shouldn't take the rest of the bundle down with it, so it just goes missing instead
			$item = Item::safeNbtDeserialize($entry, "Bundle contents");
			if(!$item->isNull()){
				$this->contents[] = $item;
			}
		}
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if(count($this->contents) === 0){
			$tag->removeTag(self::TAG_CONTENTS);
			return;
		}

		$entries = [];
		foreach($this->contents as $slot => $item){
			$entries[] = $item->nbtSerialize($slot);
		}
		$tag->setTag(self::TAG_CONTENTS, new ListTag($entries));
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function __clone(){
		parent::__clone();
		$this->contents = array_map(fn(Item $item) => clone $item, $this->contents);
	}
}
