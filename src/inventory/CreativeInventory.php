<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Item;
use pocketmine\lang\Translatable;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\utils\DestructorCallbackTrait;
use pocketmine\utils\Filesystem;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use function array_map;
use function base64_decode;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;

final class CreativeInventory{
	use SingletonTrait;
	use DestructorCallbackTrait;

	/**
	 * @var CreativeInventoryEntry[]
	 * @phpstan-var array<int, CreativeInventoryEntry>
	 */
	private array $creative = [];

	/** @phpstan-var ObjectSet<\Closure() : void> */
	private ObjectSet $contentChangedCallbacks;

	private function __construct(){
		$this->contentChangedCallbacks = new ObjectSet();

		$data = json_decode(Filesystem::fileGetContents(BedrockDataFiles::CREATIVE_ITEMS_JSON), true);
		if(!is_array($data) || !isset($data["groups"], $data["items"]) || !is_array($data["groups"]) || !is_array($data["items"])){
			throw new SavedDataLoadingException(BedrockDataFiles::CREATIVE_ITEMS_JSON . " should contain groups and items lists");
		}

		$categories = [];
		$groups = [];
		foreach(Utils::promoteKeys($data["groups"]) as $index => $groupData){
			if(
				!is_array($groupData) ||
				!isset($groupData["creative_category"], $groupData["name"], $groupData["icon"]) ||
				!is_int($groupData["creative_category"]) || !is_string($groupData["name"]) || !is_array($groupData["icon"])
			){
				throw new SavedDataLoadingException("Invalid creative group at index $index");
			}
			$categories[$index] = match($groupData["creative_category"]){
				1 => CreativeCategory::CONSTRUCTION,
				2 => CreativeCategory::NATURE,
				3 => CreativeCategory::EQUIPMENT,
				default => CreativeCategory::ITEMS
			};

			$icon = $groupData["name"] === "" ? null : self::deserializeItem($groupData["icon"]);
			$groups[$index] = $icon === null ? null : new CreativeGroup(
				new Translatable($groupData["name"]),
				$icon
			);
		}

		foreach(Utils::promoteKeys($data["items"]) as $index => $itemData){
			if(!is_array($itemData) || !isset($itemData["group_index"]) || !is_int($itemData["group_index"])){
				throw new SavedDataLoadingException("Invalid creative item at index $index");
			}
			$item = self::deserializeItem($itemData);
			if($item === null){ //unknown item
				continue;
			}
			$groupIndex = $itemData["group_index"];
			$this->add($item, $categories[$groupIndex] ?? CreativeCategory::ITEMS, $groups[$groupIndex] ?? null);
		}
	}

	/**
	 * @param mixed[] $data
	 */
	private static function deserializeItem(array $data) : ?Item{
		if(!isset($data["id"]) || !is_string($data["id"])){
			throw new SavedDataLoadingException("Creative item should have a string id");
		}

		$blockStatesTag = null;
		if(isset($data["block_state_b64"])){
			if(!is_string($data["block_state_b64"])){
				throw new SavedDataLoadingException("block_state_b64 should be a string");
			}
			//this is a full block palette entry (name, states, version, hashes) - only the states are of interest here
			$blockStatesTag = (new LittleEndianNbtSerializer())
				->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($data["block_state_b64"], true)))
				->mustGetCompoundTag()
				->getCompoundTag("states");
		}

		$nbt = null;
		if(isset($data["nbt_b64"])){
			if(!is_string($data["nbt_b64"])){
				throw new SavedDataLoadingException("nbt_b64 should be a string");
			}
			$nbt = (new LittleEndianNbtSerializer())
				->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($data["nbt_b64"], true)))
				->mustGetCompoundTag();
		}

		$damage = $data["damage"] ?? null;
		if($damage !== null && !is_int($damage)){
			throw new SavedDataLoadingException("damage should be an int");
		}

		return CraftingManagerFromDataHelper::deserializeItemStackFromFields(
			$data["id"],
			$damage,
			1,
			$blockStatesTag,
			$nbt
		);
	}

	/**
	 * Removes all previously added items from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function clear() : void{
		$this->creative = [];
		$this->onContentChange();
	}

	/**
	 * @return Item[]
	 * @phpstan-return array<int, Item>
	 */
	public function getAll() : array{
		return array_map(fn(CreativeInventoryEntry $entry) => $entry->getItem(), $this->creative);
	}

	/**
	 * @return CreativeInventoryEntry[]
	 * @phpstan-return array<int, CreativeInventoryEntry>
	 */
	public function getAllEntries() : array{
		return $this->creative;
	}

	public function getItem(int $index) : ?Item{
		return $this->getEntry($index)?->getItem();
	}

	public function getEntry(int $index) : ?CreativeInventoryEntry{
		return $this->creative[$index] ?? null;
	}

	public function getItemIndex(Item $item) : int{
		foreach($this->creative as $i => $d){
			if($d->matchesItem($item)){
				return $i;
			}
		}

		return -1;
	}

	/**
	 * Adds an item to the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function add(Item $item, CreativeCategory $category = CreativeCategory::ITEMS, ?CreativeGroup $group = null) : void{
		$this->creative[] = new CreativeInventoryEntry($item, $category, $group);
		$this->onContentChange();
	}

	/**
	 * Removes an item from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function remove(Item $item) : void{
		$index = $this->getItemIndex($item);
		if($index !== -1){
			unset($this->creative[$index]);
			$this->onContentChange();
		}
	}

	public function contains(Item $item) : bool{
		return $this->getItemIndex($item) !== -1;
	}

	/** @phpstan-return ObjectSet<\Closure() : void> */
	public function getContentChangedCallbacks() : ObjectSet{
		return $this->contentChangedCallbacks;
	}

	private function onContentChange() : void{
		foreach($this->contentChangedCallbacks as $callback){
			$callback();
		}
	}
}
