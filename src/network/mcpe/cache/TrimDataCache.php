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

namespace pocketmine\network\mcpe\cache;

use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\network\mcpe\protocol\TrimDataPacket;
use pocketmine\network\mcpe\protocol\types\TrimMaterial;
use pocketmine\network\mcpe\protocol\types\TrimPattern;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use function is_array;
use function is_string;
use function json_decode;

final class TrimDataCache{
	use SingletonTrait;

	private TrimDataPacket $cache;

	public function getPacket() : TrimDataPacket{
		return $this->cache ??= $this->buildPacket();
	}

	private function buildPacket() : TrimDataPacket{
		$data = json_decode(Filesystem::fileGetContents(BedrockDataFiles::TRIM_DATA_JSON), true);
		if(!is_array($data) || !isset($data["patterns"], $data["materials"]) || !is_array($data["patterns"]) || !is_array($data["materials"])){
			throw new SavedDataLoadingException(BedrockDataFiles::TRIM_DATA_JSON . " should contain patterns and materials lists");
		}

		$patterns = [];
		foreach($data["patterns"] as $pattern){
			if(!is_array($pattern) || !isset($pattern["itemName"], $pattern["patternId"]) || !is_string($pattern["itemName"]) || !is_string($pattern["patternId"])){
				throw new SavedDataLoadingException("Invalid trim pattern entry");
			}
			$patterns[] = new TrimPattern($pattern["itemName"], $pattern["patternId"]);
		}

		$materials = [];
		foreach($data["materials"] as $material){
			if(
				!is_array($material) ||
				!isset($material["itemName"], $material["materialId"], $material["color"]) ||
				!is_string($material["itemName"]) || !is_string($material["materialId"]) || !is_string($material["color"])
			){
				throw new SavedDataLoadingException("Invalid trim material entry");
			}
			$materials[] = new TrimMaterial($material["materialId"], $material["color"], $material["itemName"]);
		}

		return TrimDataPacket::create($patterns, $materials);
	}
}
