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

namespace pocketmine\inventory;

use pocketmine\inventory\transaction\action\validator\CallbackSlotValidator;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Bundle;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use function array_splice;
use function count;

/**
 * Lets the contents of a bundle be handled like any other inventory while the bundle itself stays an item in
 * someone else's slot. Nothing is stored here: every read and write goes straight to the bundle in that slot, so the
 * view can't go stale when the bundle gets swapped out or edited some other way.
 *
 * A bundle has no gaps. Taking out an item shifts everything after it down a slot, and anything put past the end
 * just goes at the end.
 */
final class BundleInventory extends BaseInventory{
	private bool $updatingBundle = false;

	public function __construct(
		private Inventory $holder,
		private int $holderSlot
	){
		parent::__construct();
		$this->validators->add(new CallbackSlotValidator($this->validateSlot(...)));
	}

	public function getHolder() : Inventory{ return $this->holder; }

	public function getHolderSlot() : int{ return $this->holderSlot; }

	/**
	 * Returns the bundle this view looks into, or null if something else is in that slot now.
	 */
	public function getBundle() : ?Bundle{
		$item = $this->holder->getItem($this->holderSlot);
		return $item instanceof Bundle ? $item : null;
	}

	/**
	 * Returns whether the bundle is being written back to its holder because of a change made through this view.
	 * Useful for telling those apart from someone replacing the bundle in the holder directly.
	 */
	public function isUpdatingBundle() : bool{
		return $this->updatingBundle;
	}

	public function getSize() : int{
		//the lightest anything can be is one unit, so this is as many separate stacks as a bundle can ever have
		return Bundle::MAX_WEIGHT;
	}

	public function getItem(int $index) : Item{
		$contents = $this->getBundle()?->getContents() ?? [];
		return $contents[$index] ?? VanillaItems::AIR();
	}

	public function getContents(bool $includeEmpty = false) : array{
		$contents = $this->getBundle()?->getContents() ?? [];
		if($includeEmpty){
			for($i = count($contents), $size = $this->getSize(); $i < $size; ++$i){
				$contents[$i] = VanillaItems::AIR();
			}
		}
		return $contents;
	}

	public function setItem(int $index, Item $item) : void{
		$contents = $this->getBundle()?->getContents() ?? [];
		if($item->isNull() && $index < count($contents) - 1){
			//everything after this slot moves down one, so it's more than just this slot changing
			$oldContents = $this->getContents(true);
			$this->internalSetItem($index, $item);
			$this->onContentChange($oldContents);
			return;
		}
		parent::setItem($index, $item);
	}

	protected function internalSetItem(int $index, Item $item) : void{
		$bundle = $this->getBundle() ?? throw new \LogicException("The bundle this inventory belongs to is gone");
		$contents = $bundle->getContents();
		if($item->isNull()){
			if(isset($contents[$index])){
				array_splice($contents, $index, 1);
			}
		}elseif($index < count($contents)){
			$contents[$index] = $item;
		}else{
			$contents[] = $item;
		}
		$this->writeBundle($bundle->setContents($contents));
	}

	protected function internalSetContents(array $items) : void{
		$bundle = $this->getBundle() ?? throw new \LogicException("The bundle this inventory belongs to is gone");
		$this->writeBundle($bundle->setContents($items));
	}

	private function writeBundle(Bundle $bundle) : void{
		$this->updatingBundle = true;
		try{
			$this->holder->setItem($this->holderSlot, $bundle);
		}finally{
			$this->updatingBundle = false;
		}
	}

	private function validateSlot(Inventory $inventory, Item $item, int $slot) : ?TransactionValidationException{
		$bundle = $this->getBundle();
		if($bundle === null){
			return new TransactionValidationException("The bundle this inventory belongs to is gone");
		}
		if(!Bundle::canStore($item)){
			return new TransactionValidationException("This item can't be put in a bundle");
		}

		$weight = $bundle->getWeight() - Bundle::getItemWeight($this->getItem($slot)) + Bundle::getItemWeight($item);
		if($weight > Bundle::MAX_WEIGHT){
			return new TransactionValidationException("Bundle would be overfilled ($weight/" . Bundle::MAX_WEIGHT . ")");
		}
		return null;
	}
}
