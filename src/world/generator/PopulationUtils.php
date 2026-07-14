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

namespace pocketmine\world\generator;

use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

/**
 * @internal
 */
final class PopulationUtils{

	private static function setOrGenerateChunk(SimpleChunkManager $manager, Generator $generator, int $chunkX, int $chunkZ, ?Chunk $chunk) : Chunk{
		$manager->setChunk($chunkX, $chunkZ, $chunk ?? new Chunk([], false));
		if($chunk === null){
			$generator->generateChunk($manager, $chunkX, $chunkZ);
			$chunk = $manager->getChunk($chunkX, $chunkZ);
			if($chunk === null){
				throw new AssumptionFailedError("We just set this chunk, so it must exist");
			}
		}
		return $chunk;
	}

	/**
	 * @param Chunk[]|null[] $adjacentChunks
	 * @phpstan-param array<int, Chunk|null> $adjacentChunks
	 *
	 * @return Chunk[]|Chunk[][]
	 * @phpstan-return array{Chunk, array<int, Chunk>}
	 */
	public static function populateChunkWithAdjacents(int $minY, int $maxY, Generator $generator, int $chunkX, int $chunkZ, ?Chunk $centerChunk, array $adjacentChunks) : array{
		$manager = new SimpleChunkManager($minY, $maxY);
		self::setOrGenerateChunk($manager, $generator, $chunkX, $chunkZ, $centerChunk);

		$resultChunks = []; //this is just to keep phpstan's type inference happy
		foreach($adjacentChunks as $relativeChunkHash => $c){
			World::getXZ($relativeChunkHash, $relativeX, $relativeZ);
			$resultChunks[$relativeChunkHash] = self::setOrGenerateChunk($manager, $generator, $chunkX + $relativeX, $chunkZ + $relativeZ, $c);
		}
		$adjacentChunks = $resultChunks;

		$generator->populateChunk($manager, $chunkX, $chunkZ);
		$centerChunk = $manager->getChunk($chunkX, $chunkZ);
		if($centerChunk === null){
			throw new AssumptionFailedError("We just generated this chunk, so it must exist");
		}
		$centerChunk->setPopulated();
		return [$centerChunk, $adjacentChunks];
	}
}
