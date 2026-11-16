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

namespace pocketmine\world\generator\normal;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\object\OreType;
use pocketmine\world\generator\populator\Ore;
use pocketmine\world\World;

/**
 * The normal terrain, carried down to the bottom of the world like vanilla since 1.18: deepslate below y=0 with its
 * own ores and tuff, and the bedrock floor at y=-64 instead of y=0.
 *
 * Worlds made with the plain "normal" generator keep generating exactly what they always did, since a chunk generated
 * here next to one generated there would leave a hole under the old bedrock floor.
 */
class Overworld extends Normal{
	private Ore $deepOres;
	private Random $deepRandom;

	public function __construct(int $seed, string $preset){
		parent::__construct($seed, $preset);

		$this->deepRandom = new Random($seed);
		$deepslate = VanillaBlocks::DEEPSLATE();
		$this->deepOres = new Ore();
		$this->deepOres->setOreTypes([
			new OreType(VanillaBlocks::TUFF(), $deepslate, 4, 33, World::Y_MIN, 0),
			new OreType(VanillaBlocks::GRAVEL(), $deepslate, 4, 24, World::Y_MIN, 0),
			new OreType(VanillaBlocks::DEEPSLATE_COAL_ORE(), $deepslate, 4, 16, -8, 0),
			new OreType(VanillaBlocks::DEEPSLATE_IRON_ORE(), $deepslate, 10, 8, World::Y_MIN, 0),
			new OreType(VanillaBlocks::DEEPSLATE_COPPER_ORE(), $deepslate, 4, 10, -16, 0),
			new OreType(VanillaBlocks::DEEPSLATE_GOLD_ORE(), $deepslate, 4, 8, World::Y_MIN, 0),
			new OreType(VanillaBlocks::DEEPSLATE_REDSTONE_ORE(), $deepslate, 8, 7, World::Y_MIN, 0),
			new OreType(VanillaBlocks::DEEPSLATE_LAPIS_LAZULI_ORE(), $deepslate, 4, 6, World::Y_MIN, 0),
			new OreType(VanillaBlocks::DEEPSLATE_DIAMOND_ORE(), $deepslate, 4, 7, World::Y_MIN, -16)
		]);
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
		parent::generateChunk($world, $chunkX, $chunkZ);

		$chunk = $world->getChunk($chunkX, $chunkZ) ?? throw new \InvalidArgumentException("Chunk $chunkX $chunkZ does not yet exist");
		$deepslate = VanillaBlocks::DEEPSLATE()->getStateId();
		$bedrock = VanillaBlocks::BEDROCK()->getStateId();

		//below zero is solid deepslate, flood-filled a whole sub chunk at a time
		for($y = World::Y_MIN >> SubChunk::COORD_BIT_SIZE; $y < 0; ++$y){
			$old = $chunk->getSubChunk($y);
			$chunk->setSubChunk($y, new SubChunk(Block::EMPTY_STATE_ID, [new PalettedBlockArray($deepslate)], $old->getBiomeArray()));
		}

		$this->random->setSeed(0xdee9 ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
		for($x = 0; $x < Chunk::EDGE_LENGTH; ++$x){
			for($z = 0; $z < Chunk::EDGE_LENGTH; ++$z){
				//the parent's floor sits at y=0, which is now just the top of the deepslate
				$chunk->setBlockStateId($x, 0, $z, $deepslate);

				//vanilla's floor is a flat layer with a few blocks poking up to 4 above it
				$chunk->setBlockStateId($x, World::Y_MIN, $z, $bedrock);
				for($y = 1; $y < 5; ++$y){
					if($this->random->nextBoundedInt(5) >= $y){
						$chunk->setBlockStateId($x, World::Y_MIN + $y, $z, $bedrock);
					}
				}
			}
		}
	}

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
		parent::populateChunk($world, $chunkX, $chunkZ);

		$this->deepRandom->setSeed(0xd33b ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
		$this->deepOres->populate($world, $chunkX, $chunkZ, $this->deepRandom);
	}
}
