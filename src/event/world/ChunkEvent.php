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

namespace pocketmine\event\world;

use pocketmine\world\format\Chunk;
use pocketmine\world\World;

/**
 * Chunk-related events
 */
abstract class ChunkEvent extends WorldEvent{
	public function __construct(
		World $world,
		private int $chunkX,
		private int $chunkZ,
		private Chunk $chunk
	){
		parent::__construct($world);
	}

	public function getChunk() : Chunk{
		return $this->chunk;
	}

	public function getChunkX() : int{ return $this->chunkX; }

	public function getChunkZ() : int{ return $this->chunkZ; }
}
