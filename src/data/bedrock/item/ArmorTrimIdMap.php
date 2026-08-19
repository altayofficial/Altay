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

namespace pocketmine\data\bedrock\item;

use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Item;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Maps smithing templates to trim pattern IDs, and trim-eligible ingredients (ingots, crystals, etc.) to trim material
 * IDs, as used by the smithing table's armor trim recipe.
 */
final class ArmorTrimIdMap{
	use SingletonTrait;

	/** @var array<int, string> typeId => patternId */
	private array $patterns = [];
	/** @var array<int, string> typeId => materialId */
	private array $materials = [];

	private function __construct(){
		$data = json_decode(Filesystem::fileGetContents(BedrockDataFiles::TRIM_DATA_JSON), true);
		if(!is_array($data) || !isset($data["patterns"], $data["materials"]) || !is_array($data["patterns"]) || !is_array($data["materials"])){
			throw new SavedDataLoadingException(BedrockDataFiles::TRIM_DATA_JSON . " should contain patterns and materials lists");
		}

		foreach($data["patterns"] as $pattern){
			if(!is_array($pattern) || !isset($pattern["itemName"], $pattern["patternId"]) || !is_string($pattern["itemName"]) || !is_string($pattern["patternId"])){
				throw new SavedDataLoadingException("Invalid trim pattern entry");
			}
			$item = CraftingManagerFromDataHelper::deserializeItemStackFromFields($pattern["itemName"], null, 1, null, null);
			if($item !== null){
				$this->patterns[$item->getTypeId()] = $pattern["patternId"];
			}
		}

		foreach($data["materials"] as $material){
			if(!is_array($material) || !isset($material["itemName"], $material["materialId"]) || !is_string($material["itemName"]) || !is_string($material["materialId"])){
				throw new SavedDataLoadingException("Invalid trim material entry");
			}
			$item = CraftingManagerFromDataHelper::deserializeItemStackFromFields($material["itemName"], null, 1, null, null);
			if($item !== null){
				$this->materials[$item->getTypeId()] = $material["materialId"];
			}
		}
	}

	public function getPatternId(Item $template) : ?string{
		return $this->patterns[$template->getTypeId()] ?? null;
	}

	public function getMaterialId(Item $ingredient) : ?string{
		return $this->materials[$ingredient->getTypeId()] ?? null;
	}
}
