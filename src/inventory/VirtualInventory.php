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

use pocketmine\utils\ObjectSet;
use function strlen;

/**
 * A container screen that isn't backed by any block or entity, so you can show one anywhere with
 * Player::setCurrentWindow(). Item moves go through InventoryTransactionEvent as usual, then through the
 * slot change filters, which is where menus usually live. A read only menu is just a filter that refuses:
 *
 *     $inv->getSlotChangeFilters()->add(new CallbackSlotChangeFilter(fn(Player $p, SlotChangeAction $a) : bool => false));
 *
 * Several players can view the same instance at once, just like a chest.
 *
 * @phpstan-import-type SlotChangeFilters from SlotChangeFilteredInventory
 */
class VirtualInventory extends SimpleInventory implements SlotChangeFilteredInventory{
	public const MIN_SIZE = 1;
	public const MAX_SIZE = 54;
	public const MAX_TITLE_LENGTH = 256;

	/** @phpstan-var SlotChangeFilters */
	private ObjectSet $slotChangeFilters;

	/**
	 * @param string $title Shown as the screen heading. The client translates it if it's a localization key.
	 */
	public function __construct(int $size, private string $title){
		if($size < self::MIN_SIZE || $size > self::MAX_SIZE){
			throw new \InvalidArgumentException("Virtual inventory size must be between " . self::MIN_SIZE . " and " . self::MAX_SIZE . ", got $size");
		}
		if(strlen($title) > self::MAX_TITLE_LENGTH){
			throw new \InvalidArgumentException("Virtual inventory title must be at most " . self::MAX_TITLE_LENGTH . " bytes, got " . strlen($title));
		}
		parent::__construct($size);
		$this->slotChangeFilters = new ObjectSet();
	}

	/**
	 * The title is only sent when the screen opens, so viewers keep the old one until they reopen it.
	 */
	public function getTitle() : string{
		return $this->title;
	}

	public function getSlotChangeFilters() : ObjectSet{
		return $this->slotChangeFilters;
	}
}
