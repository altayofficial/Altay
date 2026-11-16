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

namespace pocketmine\world\format;

use function array_map;
use function count;

class SubChunk{
	public const COORD_BIT_SIZE = 4;
	public const COORD_MASK = ~(~0 << self::COORD_BIT_SIZE);
	public const EDGE_LENGTH = 1 << self::COORD_BIT_SIZE;

	/**
	 * One read-only LightArray per uniform level, shared by every sub chunk that has that light everywhere. Every
	 * LightArray costs about 2.5 KB no matter what's in it, and most sub chunks are all dark or all lit, so this is
	 * most of the memory a loaded chunk would otherwise take.
	 *
	 * @var LightArray[]
	 * @phpstan-var array<int, LightArray>
	 */
	private static array $uniformLightArrays = [];

	private bool $skyLightShared = false;
	private bool $blockLightShared = false;

	/**
	 * SubChunk constructor.
	 *
	 * @param PalettedBlockArray[] $blockLayers
	 * @phpstan-param list<PalettedBlockArray> $blockLayers
	 */
	public function __construct(
		private int $emptyBlockId,
		private array $blockLayers,
		private PalettedBlockArray $biomes,
		private ?LightArray $skyLight = null,
		private ?LightArray $blockLight = null
	){}

	/**
	 * Returns whether this subchunk contains any non-air blocks.
	 * This function will do a slow check, usually by garbage collecting first.
	 * This is typically useful for disk saving.
	 */
	public function isEmptyAuthoritative() : bool{
		$this->collectGarbage();
		return $this->isEmptyFast();
	}

	/**
	 * Returns a non-authoritative bool to indicate whether the chunk contains any blocks.
	 * This may report non-empty erroneously if the chunk has been modified and not garbage-collected.
	 */
	public function isEmptyFast() : bool{
		return count($this->blockLayers) === 0;
	}

	/**
	 * Returns the block used as the default. This is assumed to refer to air.
	 * If all the blocks in a subchunk layer are equal to this block, the layer is assumed to be empty.
	 */
	public function getEmptyBlockId() : int{ return $this->emptyBlockId; }

	public function getBlockStateId(int $x, int $y, int $z) : int{
		if(count($this->blockLayers) === 0){
			return $this->emptyBlockId;
		}
		return $this->blockLayers[0]->get($x, $y, $z);
	}

	public function setBlockStateId(int $x, int $y, int $z, int $block) : void{
		if(count($this->blockLayers) === 0){
			$this->blockLayers[] = new PalettedBlockArray($this->emptyBlockId);
		}
		$this->blockLayers[0]->set($x, $y, $z, $block);
	}

	/**
	 * @return PalettedBlockArray[]
	 * @phpstan-return list<PalettedBlockArray>
	 */
	public function getBlockLayers() : array{
		return $this->blockLayers;
	}

	public function getHighestBlockAt(int $x, int $z) : ?int{
		if(count($this->blockLayers) === 0){
			return null;
		}
		for($y = self::EDGE_LENGTH - 1; $y >= 0; --$y){
			if($this->blockLayers[0]->get($x, $y, $z) !== $this->emptyBlockId){
				return $y;
			}
		}

		return null; //highest block not in this subchunk
	}

	public function getBiomeArray() : PalettedBlockArray{ return $this->biomes; }

	/**
	 * Returns this sub chunk's own sky light array, which can be written to. Use getBlockSkyLightAt() to only read,
	 * since this has to give the sub chunk a private copy of a shared uniform array first.
	 */
	public function getBlockSkyLightArray() : LightArray{
		if($this->skyLightShared && $this->skyLight !== null){
			$this->skyLightShared = false;
			return $this->skyLight = clone $this->skyLight;
		}
		return $this->skyLight ??= LightArray::fill(0);
	}

	public function getBlockSkyLightAt(int $x, int $y, int $z) : int{
		return $this->skyLight?->get($x, $y, $z) ?? 0;
	}

	public function setBlockSkyLightArray(LightArray $data) : void{
		$this->skyLight = $data;
		$this->skyLightShared = false;
	}

	/**
	 * Sets the same sky light level everywhere in this sub chunk without giving it an array of its own.
	 */
	public function setUniformBlockSkyLight(int $level) : void{
		$this->skyLight = $level === 0 ? null : self::getUniformLightArray($level);
		$this->skyLightShared = $level !== 0;
	}

	/**
	 * Returns this sub chunk's own block light array, which can be written to. Use getBlockLightAt() to only read,
	 * since this has to give the sub chunk a private copy of a shared uniform array first.
	 */
	public function getBlockLightArray() : LightArray{
		if($this->blockLightShared && $this->blockLight !== null){
			$this->blockLightShared = false;
			return $this->blockLight = clone $this->blockLight;
		}
		return $this->blockLight ??= LightArray::fill(0);
	}

	public function getBlockLightAt(int $x, int $y, int $z) : int{
		return $this->blockLight?->get($x, $y, $z) ?? 0;
	}

	public function setBlockLightArray(LightArray $data) : void{
		$this->blockLight = $data;
		$this->blockLightShared = false;
	}

	/**
	 * Sets the same block light level everywhere in this sub chunk without giving it an array of its own.
	 */
	public function setUniformBlockLight(int $level) : void{
		$this->blockLight = $level === 0 ? null : self::getUniformLightArray($level);
		$this->blockLightShared = $level !== 0;
	}

	private static function getUniformLightArray(int $level) : LightArray{
		if($level < 0 || $level > 15){
			throw new \InvalidArgumentException("Light level must be in range 0-15, got $level");
		}
		return self::$uniformLightArrays[$level] ??= LightArray::fill($level);
	}

	/**
	 * @return mixed[]
	 */
	public function __debugInfo() : array{
		return [];
	}

	public function collectGarbage() : void{
		$cleanedLayers = [];
		foreach($this->blockLayers as $layer){
			$layer->collectGarbage();

			if($layer->getBitsPerBlock() !== 0 || $layer->get(0, 0, 0) !== $this->emptyBlockId){
				$cleanedLayers[] = $layer;
			}
		}
		$this->blockLayers = $cleanedLayers;
		$this->biomes->collectGarbage();

		if($this->skyLight !== null && !$this->skyLightShared && $this->skyLight->isUniform(0)){
			$this->skyLight = null;
		}
		if($this->blockLight !== null && !$this->blockLightShared && $this->blockLight->isUniform(0)){
			$this->blockLight = null;
		}
	}

	public function __clone(){
		$this->blockLayers = array_map(function(PalettedBlockArray $array) : PalettedBlockArray{
			return clone $array;
		}, $this->blockLayers);
		$this->biomes = clone $this->biomes;

		//shared uniform arrays are never written to, so the copy can keep pointing at them
		if($this->skyLight !== null && !$this->skyLightShared){
			$this->skyLight = clone $this->skyLight;
		}
		if($this->blockLight !== null && !$this->blockLightShared){
			$this->blockLight = clone $this->blockLight;
		}
	}
}
