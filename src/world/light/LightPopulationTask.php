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

namespace pocketmine\world\light;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\LightArray;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;
use function igbinary_serialize;
use function igbinary_unserialize;

class LightPopulationTask extends AsyncTask{
	private const TLS_KEY_COMPLETION_CALLBACK = "onCompletion";

	public string $chunk;

	private string $resultHeightMap;
	private string $resultSkyLightArrays;
	private string $resultBlockLightArrays;

	/**
	 * @phpstan-param \Closure(array<int, LightArray> $blockLight, array<int, LightArray> $skyLight, non-empty-list<int> $heightMap) : void $onCompletion
	 */
	public function __construct(Chunk $chunk, \Closure $onCompletion){
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->storeLocal(self::TLS_KEY_COMPLETION_CALLBACK, $onCompletion);
	}

	public function onRun() : void{
		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);

		$manager = new SimpleChunkManager(World::Y_MIN, World::Y_MAX);
		$manager->setChunk(0, 0, $chunk);

		$blockFactory = RuntimeBlockStateRegistry::getInstance();
		foreach([
			"Block" => new BlockLightUpdate(new SubChunkExplorer($manager), $blockFactory->lightFilter, $blockFactory->light),
			"Sky" => new SkyLightUpdate(new SubChunkExplorer($manager), $blockFactory->lightFilter, $blockFactory->blocksDirectSkyLight),
		] as $name => $update){
			$update->recalculateChunk(0, 0);
			$update->execute();
		}

		$chunk->setLightPopulated();

		$this->resultHeightMap = igbinary_serialize($chunk->getHeightMapArray());
		$skyLightArrays = [];
		$blockLightArrays = [];
		foreach($chunk->getSubChunks() as $y => $subChunk){
			$skyLightArrays[$y] = $subChunk->getBlockSkyLightArray();
			$blockLightArrays[$y] = $subChunk->getBlockLightArray();
		}
		$this->resultSkyLightArrays = igbinary_serialize($skyLightArrays);
		$this->resultBlockLightArrays = igbinary_serialize($blockLightArrays);
	}

	public function onCompletion() : void{
		/**
		 * @var int[] $heightMapArray
		 * @phpstan-var non-empty-list<int> $heightMapArray
		 */
		$heightMapArray = igbinary_unserialize($this->resultHeightMap);

		/** @var LightArray[] $skyLightArrays */
		$skyLightArrays = igbinary_unserialize($this->resultSkyLightArrays);
		/** @var LightArray[] $blockLightArrays */
		$blockLightArrays = igbinary_unserialize($this->resultBlockLightArrays);

		/**
		 * @var \Closure
		 * @phpstan-var \Closure(array<int, LightArray> $blockLight, array<int, LightArray> $skyLight, non-empty-list<int> $heightMap) : void
		 */
		$callback = $this->fetchLocal(self::TLS_KEY_COMPLETION_CALLBACK);
		$callback($blockLightArrays, $skyLightArrays, $heightMapArray);
	}
}
