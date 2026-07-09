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

namespace pocketmine\network\mcpe\cache;

use pocketmine\color\Color;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use function zlib_decode;

class StaticPacketCache{
	use SingletonTrait;

	private static function loadCompoundFromFile(string $filePath) : CompoundTag{
		$raw = zlib_decode(Filesystem::fileGetContents($filePath));
		if($raw === false){
			throw new SavedDataLoadingException("Failed to decompress $filePath");
		}
		return (new BigEndianNbtSerializer())->read($raw)->mustGetCompoundTag();
	}

	/**
	 * Loads biome definitions from a bedrock-network-data biome_definitions.nbt, which stores the definitions in the
	 * same string-pooled layout as BiomeDefinitionListPacket.
	 *
	 * @return list<BiomeDefinitionEntry>
	 */
	private static function loadBiomeDefinitionModel(string $filePath) : array{
		$root = self::loadCompoundFromFile($filePath);

		$stringListTag = $root->getListTag("biomeStringList") ?? throw new SavedDataLoadingException("$filePath missing biomeStringList");
		$strings = [];
		foreach($stringListTag as $i => $tag){
			if(!($tag instanceof StringTag)){
				throw new SavedDataLoadingException("biomeStringList should only contain strings");
			}
			$strings[$i] = $tag->getValue();
		}
		$locateString = function(int $index) use ($strings, $filePath) : string{
			return $strings[$index] ?? throw new SavedDataLoadingException("$filePath refers to unknown string index $index");
		};

		$biomeDataTag = $root->getListTag("biomeData") ?? throw new SavedDataLoadingException("$filePath missing biomeData");
		$entries = [];
		foreach($biomeDataTag as $entryTag){
			if(!($entryTag instanceof CompoundTag)){
				throw new SavedDataLoadingException("biomeData should only contain compounds");
			}
			$data = $entryTag->getCompoundTag("data") ?? throw new SavedDataLoadingException("Biome entry is missing data");

			$tags = null;
			$tagsTag = $data->getCompoundTag("tags")?->getListTag("tags");
			if($tagsTag !== null){
				$tags = [];
				foreach($tagsTag as $tagIndexTag){
					if(!($tagIndexTag instanceof ShortTag)){
						throw new SavedDataLoadingException("Biome tag list should only contain shorts");
					}
					$tags[] = $locateString($tagIndexTag->getValue() & 0xffff);
				}
			}

			$entries[] = new BiomeDefinitionEntry(
				$locateString($entryTag->getShort("index") & 0xffff),
				$data->getShort("id") & 0xffff,
				$data->getFloat("temperature"),
				$data->getFloat("downfall"),
				$data->getFloat("foliageSnow"),
				$data->getFloat("depth"),
				$data->getFloat("scale"),
				Color::fromARGB($data->getInt("mapWaterColorARGB") & 0xffffffff),
				$data->getByte("rain") !== 0,
				$tags,
			);
		}

		return $entries;
	}

	private static function make() : self{
		return new self(
			BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(BedrockDataFiles::BIOME_DEFINITIONS_NBT)),
			AvailableActorIdentifiersPacket::create(new CacheableNbt(self::loadCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_NBT)))
		);
	}

	public function __construct(
		private BiomeDefinitionListPacket $biomeDefs,
		private AvailableActorIdentifiersPacket $availableActorIdentifiers
	){}

	public function getBiomeDefs() : BiomeDefinitionListPacket{
		return $this->biomeDefs;
	}

	public function getAvailableActorIdentifiers() : AvailableActorIdentifiersPacket{
		return $this->availableActorIdentifiers;
	}
}
