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

use pocketmine\block\utils\DirtType;
use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\World;
use function abs;
use function mt_getrandmax;
use function mt_rand;

class MossBlock extends Opaque{

	private const SPREAD_RADIUS = 3;
	private const SPREAD_VERTICAL_RANGE = 5;

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if(!$item instanceof Fertilizer || $face !== Facing::UP){
			return false;
		}

		$world = $this->position->getWorld();
		$this->spreadMoss($world);
		$this->populateFlora($world);
		$world->addParticle($this->position->add(0.5, 1.5, 0.5), new HappyVillagerParticle());
		$item->pop();

		return true;
	}

	private function spreadMoss(World $world) : void{
		$bx = $this->position->getFloorX();
		$by = $this->position->getFloorY();
		$bz = $this->position->getFloorZ();

		for($x = $bx - self::SPREAD_RADIUS; $x <= $bx + self::SPREAD_RADIUS; $x++){
			for($z = $bz - self::SPREAD_RADIUS; $z <= $bz + self::SPREAD_RADIUS; $z++){
				for($y = $by + self::SPREAD_VERTICAL_RANGE; $y >= $by - self::SPREAD_VERTICAL_RANGE; $y--){
					if(!$world->isInWorld($x, $y, $z)){
						continue;
					}

					$block = $world->getBlockAt($x, $y, $z);
					if(!$this->canConvertToMoss($block)){
						continue;
					}

					$isNearOrigin = abs($x - $bx) < self::SPREAD_RADIUS && abs($z - $bz) < self::SPREAD_RADIUS;
					if((mt_rand() / mt_getrandmax()) < 0.6 || $isNearOrigin){
						$world->setBlockAt($x, $y, $z, $this->getMossBlock());
						break;
					}
				}
			}
		}
	}

	private function populateFlora(World $world) : void{
		$bx = $this->position->getFloorX();
		$by = $this->position->getFloorY();
		$bz = $this->position->getFloorZ();

		for($x = $bx - self::SPREAD_RADIUS; $x <= $bx + self::SPREAD_RADIUS; $x++){
			for($z = $bz - self::SPREAD_RADIUS; $z <= $bz + self::SPREAD_RADIUS; $z++){
				for($y = $by + self::SPREAD_VERTICAL_RANGE; $y >= $by - self::SPREAD_VERTICAL_RANGE; $y--){
					if(!$world->isInWorld($x, $y, $z) || !$world->isInWorld($x, $y - 1, $z)){
						continue;
					}

					if($world->getBlockAt($x, $y, $z)->getTypeId() !== BlockTypeIds::AIR){
						continue;
					}

					$below = $world->getBlockAt($x, $y - 1, $z);
					if(!$below->isSolid()){
						continue;
					}

					if(!$this->canGrowPlantOn($below)){
						break;
					}

					$this->populateColumn($world, $x, $y, $z);
					break;
				}
			}
		}
	}

	private function populateColumn(World $world, int $x, int $y, int $z) : void{
		$roll = mt_rand() / mt_getrandmax();

		if($roll < 0.3125){
			$world->setBlockAt($x, $y, $z, VanillaBlocks::TALL_GRASS());
			return;
		}

		if($roll < 0.46875){
			$carpet = $this->getMossCarpet();
			if($carpet !== null){
				$world->setBlockAt($x, $y, $z, $carpet);
			}
			return;
		}

		if($roll < 0.53125){
			if($world->isInWorld($x, $y + 1, $z) && $world->getBlockAt($x, $y + 1, $z)->getTypeId() === BlockTypeIds::AIR){
				$world->setBlockAt($x, $y, $z, (clone VanillaBlocks::LARGE_FERN())->setTop(false));
				$world->setBlockAt($x, $y + 1, $z, (clone VanillaBlocks::LARGE_FERN())->setTop(true));
			}else{
				$world->setBlockAt($x, $y, $z, VanillaBlocks::TALL_GRASS());
			}
			return;
		}

		if($roll < 0.575){
			$world->setBlockAt($x, $y, $z, VanillaBlocks::AZALEA());
			return;
		}

		if($roll < 0.6){
			$world->setBlockAt($x, $y, $z, VanillaBlocks::FLOWERING_AZALEA());
		}
	}

	protected function getMossBlock() : Block{
		return VanillaBlocks::MOSS_BLOCK();
	}

	protected function getMossCarpet() : ?Block{
		return VanillaBlocks::MOSS_CARPET();
	}

	private function canConvertToMoss(Block $block) : bool{
		if($block instanceof Dirt){
			return $block->getDirtType() !== DirtType::COARSE;
		}

		return match($block->getTypeId()){
			BlockTypeIds::GRASS, BlockTypeIds::STONE, BlockTypeIds::MYCELIUM, BlockTypeIds::DEEPSLATE, BlockTypeIds::TUFF => true,
			default => false
		};
	}

	private function canGrowPlantOn(Block $block) : bool{
		if($block instanceof Dirt){
			return $block->getDirtType() !== DirtType::COARSE;
		}

		return match($block->getTypeId()){
			BlockTypeIds::GRASS, BlockTypeIds::PODZOL, BlockTypeIds::FARMLAND, BlockTypeIds::MYCELIUM, BlockTypeIds::MOSS_BLOCK, BlockTypeIds::PALE_MOSS_BLOCK => true,
			default => false
		};
	}
}
