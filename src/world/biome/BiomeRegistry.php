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

namespace pocketmine\world\biome;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\generator\object\TreeType;

final class BiomeRegistry{
	use SingletonTrait;

	/**
	 * @var Biome[]|\SplFixedArray
	 * @phpstan-var \SplFixedArray<Biome>
	 */
	private \SplFixedArray $biomes;

	public function __construct(){
		$this->biomes = new \SplFixedArray(Biome::MAX_BIOMES);

		$this->register(BiomeIds::OCEAN, new OceanBiome());
		$this->register(BiomeIds::PLAINS, new PlainBiome());
		$this->register(BiomeIds::DESERT, new DesertBiome());
		$this->register(BiomeIds::EXTREME_HILLS, new MountainsBiome());
		$this->register(BiomeIds::FOREST, new ForestBiome());
		$this->register(BiomeIds::TAIGA, new TaigaBiome());
		$this->register(BiomeIds::SWAMPLAND, new SwampBiome());
		$this->register(BiomeIds::RIVER, new RiverBiome());

		$this->register(BiomeIds::HELL, new HellBiome());

		$this->register(BiomeIds::ICE_PLAINS, new IcePlainsBiome());

		$this->register(BiomeIds::EXTREME_HILLS_EDGE, new SmallMountainsBiome());

		$this->register(BiomeIds::BIRCH_FOREST, new ForestBiome(TreeType::BIRCH));
	}

	public function register(int $id, Biome $biome) : void{
		$this->biomes[$id] = $biome;
		$biome->setId($id);
	}

	public function getBiome(int $id) : Biome{
		if($this->biomes[$id] === null){
			$this->register($id, new UnknownBiome());
		}

		return $this->biomes[$id];
	}
}
