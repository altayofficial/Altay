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

namespace pocketmine\block;

use pocketmine\block\utils\BlockEventHelper;
use pocketmine\item\Fertilizer;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\Shovel;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\object\TallGrass as TallGrassObject;
use pocketmine\world\sound\ItemUseOnBlockSound;
use function intdiv;
use function mt_rand;

class Grass extends Opaque{
	private static ?int $normalDirtStateId = null;

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			VanillaBlocks::DIRT()->asItem()
		];
	}

	public function isAffectedBySilkTouch() : bool{
		return true;
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		$world = $this->position->getWorld();
		$lightAbove = $world->getFullLightAt($this->position->x, $this->position->y + 1, $this->position->z);
		if($lightAbove < 4 && $world->getBlockAt($this->position->x, $this->position->y + 1, $this->position->z)->getLightFilter() >= 2){
			//grass dies
			BlockEventHelper::spread($this, VanillaBlocks::DIRT(), $this);
		}elseif($lightAbove >= 9){
			//try grass spread
			$dirtStateId = self::$normalDirtStateId ??= VanillaBlocks::DIRT()->getStateId();
			//the light engine's table is getLightFilter() + 1, so >= 3 here is the same as getLightFilter() >= 2
			$lightFilters = RuntimeBlockStateRegistry::getInstance()->lightFilter;
			$minY = $world->getMinY();
			$maxY = $world->getMaxY();
			$baseX = $this->position->getFloorX();
			$baseY = $this->position->getFloorY();
			$baseZ = $this->position->getFloorZ();
			for($i = 0; $i < 4; ++$i){
				//one draw for the whole 3x5x3 box instead of one per axis, every target is still equally likely
				$target = mt_rand(0, 44);
				$x = $baseX + ($target % 3) - 1;
				$z = $baseZ + (intdiv($target, 3) % 3) - 1;
				$y = $baseY + intdiv($target, 9) - 3;
				if($y < $minY || $y >= $maxY){
					continue;
				}

				//hmm, nearly every target is air, grass or covered dirt, so peek at the raw states before paying for a
				//full Block. Plain dirt has exactly one state and no dynamic state, so the id says it all
				$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
				if($chunk === null || $chunk->getBlockStateId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK) !== $dirtStateId){
					continue;
				}
				if($y + 1 < $maxY){
					//covered dirt is the usual hit, and whatever covers it is opaque
					if($lightFilters[$chunk->getBlockStateId($x & Chunk::COORD_MASK, $y + 1, $z & Chunk::COORD_MASK)] >= 3 || $world->getFullLightAt($x, $y + 1, $z) < 4){
						continue;
					}
				}elseif($world->getFullLightAt($x, $y + 1, $z) < 4 || $world->getBlockAt($x, $y + 1, $z)->getLightFilter() >= 2){
					continue;
				}

				BlockEventHelper::spread($world->getBlockAt($x, $y, $z), VanillaBlocks::GRASS(), $this);
			}
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->getSide(Facing::UP)->getTypeId() !== BlockTypeIds::AIR){
			return false;
		}
		$world = $this->position->getWorld();
		if($item instanceof Fertilizer){
			$item->pop();
			TallGrassObject::growGrass($world, $this->position, new Random(mt_rand()), 8, 2);

			return true;
		}
		if($face !== Facing::DOWN){
			if($item instanceof Hoe){
				$item->applyDamage(1);
				$newBlock = VanillaBlocks::FARMLAND();
				$world->addSound($this->position->add(0.5, 0.5, 0.5), new ItemUseOnBlockSound($newBlock));
				$world->setBlock($this->position, $newBlock);

				return true;
			}elseif($item instanceof Shovel){
				$item->applyDamage(1);
				$newBlock = VanillaBlocks::GRASS_PATH();
				$world->addSound($this->position->add(0.5, 0.5, 0.5), new ItemUseOnBlockSound($newBlock));
				$world->setBlock($this->position, $newBlock);

				return true;
			}
		}

		return false;
	}
}
