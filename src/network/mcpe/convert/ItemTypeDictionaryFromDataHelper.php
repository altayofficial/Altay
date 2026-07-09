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

namespace pocketmine\network\mcpe\convert;

use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function zlib_decode;

final class ItemTypeDictionaryFromDataHelper{

	/**
	 * Loads the item dictionary from a bedrock-network-data item_palette.json, optionally enriching entries with
	 * their component NBT from a gzipped big-endian item_components.nbt.
	 */
	public static function loadFromString(string $data, ?string $componentData = null) : ItemTypeDictionary{
		$table = json_decode($data, true);
		if(!is_array($table) || !isset($table["items"]) || !is_array($table["items"])){
			throw new AssumptionFailedError("Invalid item palette format");
		}

		$components = [];
		if($componentData !== null){
			$componentsRaw = zlib_decode($componentData);
			if($componentsRaw === false){
				throw new AssumptionFailedError("Failed to decompress item components");
			}
			$componentsRoot = (new BigEndianNbtSerializer())->read($componentsRaw)->mustGetCompoundTag();
			foreach($componentsRoot as $itemName => $tag){
				if($tag instanceof CompoundTag){
					$components[$itemName] = $tag;
				}
			}
		}

		$emptyNBT = new CacheableNbt(new CompoundTag());

		$params = [];
		foreach($table["items"] as $entry){
			if(!is_array($entry) || !isset($entry["name"], $entry["id"], $entry["version"], $entry["component_based"]) || !is_string($entry["name"]) || !is_int($entry["id"]) || !is_int($entry["version"]) || !is_bool($entry["component_based"])){
				throw new AssumptionFailedError("Invalid item palette entry format");
			}
			$componentNbt = $components[$entry["name"]] ?? null;
			$params[] = new ItemTypeEntry(
				$entry["name"],
				$entry["id"],
				$entry["component_based"],
				$entry["version"],
				$componentNbt === null ? $emptyNBT : new CacheableNbt($componentNbt)
			);
		}
		return new ItemTypeDictionary($params);
	}
}
