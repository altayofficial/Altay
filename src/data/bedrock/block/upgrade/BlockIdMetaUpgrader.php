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

namespace pocketmine\data\bedrock\block\upgrade;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\Utils;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

/**
 * Handles translating legacy 1.12 block ID/meta into modern blockstates.
 */
final class BlockIdMetaUpgrader{
	/**
	 * @param BlockStateData[][] $mappingTable
	 * @phpstan-param array<string, array<int, BlockStateData>> $mappingTable
	 */
	public function __construct(
		private array $mappingTable,
		private LegacyBlockIdToStringIdMap $legacyNumericIdMap
	){}

	/**
	 * @throws BlockStateDeserializeException
	 */
	public function fromStringIdMeta(string $id, int $meta) : BlockStateData{
		return $this->mappingTable[$id][$meta] ??
			$this->mappingTable[$id][0] ??
			throw new BlockStateDeserializeException("Unknown legacy block string ID $id");
	}

	/**
	 * @throws BlockStateDeserializeException
	 */
	public function fromIntIdMeta(int $id, int $meta) : BlockStateData{
		$stringId = $this->legacyNumericIdMap->legacyToString($id);
		if($stringId === null){
			throw new BlockStateDeserializeException("Unknown legacy block numeric ID $id");
		}
		return $this->fromStringIdMeta($stringId, $meta);
	}

	/**
	 * Adds a mapping of legacy block numeric ID to modern string ID. This is used for upgrading blocks from pre-1.2.13
	 * worlds (PM3). It's also needed for upgrading flower pot contents and falling blocks from PM4 worlds.
	 */
	public function addIntIdToStringIdMapping(int $intId, string $stringId) : void{
		$this->legacyNumericIdMap->add($stringId, $intId);
	}

	/**
	 * Adds a mapping of legacy block ID and meta to modern blockstate data. This may be needed for upgrading data from
	 * stored custom blocks from older versions of PocketMine-MP.
	 */
	public function addIdMetaToStateMapping(string $stringId, int $meta, BlockStateData $stateData) : void{
		if(isset($this->mappingTable[$stringId][$meta])){
			throw new \InvalidArgumentException("A mapping for $stringId:$meta already exists");
		}
		$this->mappingTable[$stringId][$meta] = $stateData;
	}

	/**
	 * Loads the legacy id+meta to blockstate mapping table from JSON data. The states in the map are old format
	 * states, so they are passed through the blockstate upgrader before being stored.
	 */
	public static function loadFromJsonString(string $data, LegacyBlockIdToStringIdMap $idMap, BlockStateUpgrader $blockStateUpgrader) : self{
		$mappingTable = [];

		$json = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
		if(!is_array($json)){
			throw new BlockStateDeserializeException("Invalid legacy block data map, expected array as root type");
		}

		foreach(Utils::promoteKeys($json) as $id => $stateList){
			if(!is_string($id) || !is_array($stateList)){
				throw new BlockStateDeserializeException("Invalid legacy block data map entry");
			}
			foreach(Utils::promoteKeys($stateList) as $meta => $stateProperties){
				if(!is_int($meta) || !is_array($stateProperties)){
					throw new BlockStateDeserializeException("Invalid legacy block data map states for $id");
				}
				$states = [];
				foreach(Utils::promoteKeys($stateProperties) as $name => $value){
					$states[(string) $name] = self::jsonValueToTag($id, (string) $name, $value);
				}
				$mappingTable[$id][$meta] = $blockStateUpgrader->upgrade(new BlockStateData($id, $states, 0));
			}
		}

		//blocks without state variants don't appear in the legacy data map, but they are still valid legacy ids
		foreach($idMap->getLegacyToStringMap() as $id){
			if(!isset($mappingTable[$id])){
				$mappingTable[$id][0] = $blockStateUpgrader->upgrade(new BlockStateData($id, [], 0));
			}
		}

		return new self($mappingTable, $idMap);
	}

	private static function jsonValueToTag(string $id, string $name, mixed $value) : Tag{
		if(is_bool($value)){
			return new ByteTag($value ? 1 : 0);
		}
		if(is_int($value)){
			return new IntTag($value);
		}
		if(is_string($value)){
			return new StringTag($value);
		}
		throw new BlockStateDeserializeException("Invalid legacy block state value type for $id ($name)");
	}
}
