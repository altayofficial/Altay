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

namespace pocketmine\world\generator\executor;

use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\PopulationUtils;

final class SyncGeneratorExecutor implements GeneratorExecutor{

	private readonly Generator $generator;
	private readonly int $worldMinY;
	private readonly int $worldMaxY;

	public function __construct(
		GeneratorExecutorSetupParameters $setupParameters
	){
		$this->generator = $setupParameters->createGenerator();
		$this->worldMinY = $setupParameters->worldMinY;
		$this->worldMaxY = $setupParameters->worldMaxY;
	}

	public function populate(int $chunkX, int $chunkZ, ?Chunk $centerChunk, array $adjacentChunks, \Closure $onCompletion) : void{
		[$centerChunk, $adjacentChunks] = PopulationUtils::populateChunkWithAdjacents(
			$this->worldMinY,
			$this->worldMaxY,
			$this->generator,
			$chunkX,
			$chunkZ,
			$centerChunk,
			$adjacentChunks
		);

		$onCompletion($centerChunk, $adjacentChunks);
	}

	public function shutdown() : void{
		//NOOP
	}
}
