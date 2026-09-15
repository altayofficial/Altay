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

use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ComposterEmptySound;
use pocketmine\world\sound\ComposterFillSound;
use pocketmine\world\sound\ComposterFillSuccessSound;
use pocketmine\world\sound\ComposterReadySound;
use function max;
use function min;
use function mt_rand;

class Composter extends Transparent{

	public const MIN_FILL_LEVEL = 0;
	public const MAX_FILL_LEVEL = 8;

	/**
	 * Fill level at which the composter stops accepting items and starts turning its contents into bone meal.
	 */
	public const FULL_FILL_LEVEL = 7;

	protected int $fillLevel = self::MIN_FILL_LEVEL;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(self::MIN_FILL_LEVEL, self::MAX_FILL_LEVEL, $this->fillLevel);
	}

	public function getFillLevel() : int{
		return $this->fillLevel;
	}

	/** @return $this */
	public function setFillLevel(int $fillLevel) : self{
		if($fillLevel < self::MIN_FILL_LEVEL || $fillLevel > self::MAX_FILL_LEVEL){
			throw new \InvalidArgumentException("Fill level must be in range " . self::MIN_FILL_LEVEL . " ... " . self::MAX_FILL_LEVEL);
		}
		$this->fillLevel = $fillLevel;
		return $this;
	}

	public function isEmpty() : bool{
		return $this->fillLevel === self::MIN_FILL_LEVEL;
	}

	/**
	 * Returns whether the composter finished composting and is ready to be emptied.
	 */
	public function isReady() : bool{
		return $this->fillLevel === self::MAX_FILL_LEVEL;
	}

	/**
	 * Returns the height of the compost inside the composter. An empty composter still has a floor 2/16 thick.
	 */
	private function getContentHeight() : float{
		return min(16, max(2, 1 + ($this->fillLevel * 2))) / 16;
	}

	protected function recalculateCollisionBoxes() : array{
		$result = [
			AxisAlignedBB::one()->trim(Facing::UP, 1 - $this->getContentHeight())
		];

		foreach(Facing::HORIZONTAL as $facing){
			$result[] = AxisAlignedBB::one()->trim($facing, 14 / 16);
		}
		return $result;
	}

	public function getSupportType(int $facing) : SupportType{
		return $facing === Facing::UP ? SupportType::EDGE : SupportType::NONE;
	}

	public function getFlameEncouragement() : int{
		return 5;
	}

	public function getFlammability() : int{
		return 20;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		$world = $this->position->getWorld();
		$soundPos = $this->position->add(0.5, 0.5, 0.5);

		if($this->isReady()){
			$world->setBlock($this->position, $this->setFillLevel(self::MIN_FILL_LEVEL));
			$world->addSound($soundPos, new ComposterEmptySound());
			$world->dropItem($this->position->add(0.5, 0.85, 0.5), VanillaItems::BONE_MEAL());
			return true;
		}

		if($this->fillLevel >= self::FULL_FILL_LEVEL){
			return true;
		}

		$chance = self::getCompostChance($item);
		if($chance <= 0){
			return false;
		}

		$item->pop();

		if(mt_rand(1, 100) > $chance){
			$world->addSound($soundPos, new ComposterFillSound());
			return true;
		}

		$world->setBlock($this->position, $this->setFillLevel($this->fillLevel + 1));
		if($this->fillLevel === self::FULL_FILL_LEVEL){
			$world->addSound($soundPos, new ComposterReadySound());
			$world->scheduleDelayedBlockUpdate($this->position, 20);
		}else{
			$world->addSound($soundPos, new ComposterFillSuccessSound());
		}

		return true;
	}

	public function onScheduledUpdate() : void{
		if($this->fillLevel === self::FULL_FILL_LEVEL){
			$this->position->getWorld()->setBlock($this->position, $this->setFillLevel(self::MAX_FILL_LEVEL));
		}
	}

	/**
	 * Returns the percentage of chance that the given item has to raise the fill level of a composter,
	 * or 0 if the item cannot be composted at all.
	 */
	public static function getCompostChance(Item $item) : int{
		if($item instanceof ItemBlock){
			return self::getBlockCompostChance($item->getBlock()->getTypeId());
		}

		return match($item->getTypeId()){
			ItemTypeIds::BEETROOT_SEEDS,
			ItemTypeIds::DRIED_KELP,
			ItemTypeIds::GLOW_BERRIES,
			ItemTypeIds::MELON_SEEDS,
			ItemTypeIds::PITCHER_POD,
			ItemTypeIds::PUMPKIN_SEEDS,
			ItemTypeIds::SWEET_BERRIES,
			ItemTypeIds::TORCHFLOWER_SEEDS,
			ItemTypeIds::WHEAT_SEEDS => 30,
			ItemTypeIds::MELON => 50,
			ItemTypeIds::APPLE,
			ItemTypeIds::BEETROOT,
			ItemTypeIds::CARROT,
			ItemTypeIds::COCOA_BEANS,
			ItemTypeIds::POTATO => 65,
			ItemTypeIds::BAKED_POTATO,
			ItemTypeIds::BREAD,
			ItemTypeIds::COOKIE => 85,
			ItemTypeIds::PUMPKIN_PIE => 100,
			default => 0
		};
	}

	private static function getBlockCompostChance(int $blockTypeId) : int{
		return match($blockTypeId){
			BlockTypeIds::ACACIA_LEAVES,
			BlockTypeIds::ACACIA_SAPLING,
			BlockTypeIds::AZALEA_LEAVES,
			BlockTypeIds::BIRCH_LEAVES,
			BlockTypeIds::BIRCH_SAPLING,
			BlockTypeIds::CHERRY_LEAVES,
			BlockTypeIds::CHERRY_SAPLING,
			BlockTypeIds::DARK_OAK_LEAVES,
			BlockTypeIds::DARK_OAK_SAPLING,
			BlockTypeIds::GRASS,
			BlockTypeIds::HANGING_ROOTS,
			BlockTypeIds::JUNGLE_LEAVES,
			BlockTypeIds::JUNGLE_SAPLING,
			BlockTypeIds::MANGROVE_LEAVES,
			BlockTypeIds::MANGROVE_ROOTS,
			BlockTypeIds::OAK_LEAVES,
			BlockTypeIds::OAK_SAPLING,
			BlockTypeIds::PINK_PETALS,
			BlockTypeIds::SEAGRASS,
			BlockTypeIds::SMALL_DRIPLEAF,
			BlockTypeIds::SPRUCE_LEAVES,
			BlockTypeIds::SPRUCE_SAPLING,
			BlockTypeIds::SWEET_BERRY_BUSH,
			BlockTypeIds::TALL_GRASS => 30,
			BlockTypeIds::CACTUS,
			BlockTypeIds::DOUBLE_TALLGRASS,
			BlockTypeIds::DRIED_KELP,
			BlockTypeIds::FLOWERING_AZALEA_LEAVES,
			BlockTypeIds::GLOW_LICHEN,
			BlockTypeIds::NETHER_SPROUTS,
			BlockTypeIds::SUGARCANE,
			BlockTypeIds::TWISTING_VINES,
			BlockTypeIds::VINES,
			BlockTypeIds::WEEPING_VINES => 50,
			BlockTypeIds::ALLIUM,
			BlockTypeIds::AZALEA,
			BlockTypeIds::AZURE_BLUET,
			BlockTypeIds::BIG_DRIPLEAF_HEAD,
			BlockTypeIds::BLUE_ORCHID,
			BlockTypeIds::BROWN_MUSHROOM,
			BlockTypeIds::CARVED_PUMPKIN,
			BlockTypeIds::CORNFLOWER,
			BlockTypeIds::CRIMSON_FUNGUS,
			BlockTypeIds::CRIMSON_ROOTS,
			BlockTypeIds::DANDELION,
			BlockTypeIds::FERN,
			BlockTypeIds::LARGE_FERN,
			BlockTypeIds::LILY_OF_THE_VALLEY,
			BlockTypeIds::LILY_PAD,
			BlockTypeIds::MELON,
			BlockTypeIds::MOSS_BLOCK,
			BlockTypeIds::MUSHROOM_STEM,
			BlockTypeIds::NETHER_WART,
			BlockTypeIds::ORANGE_TULIP,
			BlockTypeIds::OXEYE_DAISY,
			BlockTypeIds::PINK_TULIP,
			BlockTypeIds::POPPY,
			BlockTypeIds::PUMPKIN,
			BlockTypeIds::RED_MUSHROOM,
			BlockTypeIds::RED_TULIP,
			BlockTypeIds::SEA_PICKLE,
			BlockTypeIds::SHROOMLIGHT,
			BlockTypeIds::SPORE_BLOSSOM,
			BlockTypeIds::WARPED_FUNGUS,
			BlockTypeIds::WARPED_ROOTS,
			BlockTypeIds::WHEAT,
			BlockTypeIds::WHITE_TULIP,
			BlockTypeIds::WITHER_ROSE => 65,
			//BlockTypeIds::STRAW_BED => 65, //TODO: uncomment when bedrock-1.26.50 is merged
			BlockTypeIds::BROWN_MUSHROOM_BLOCK,
			BlockTypeIds::FLOWERING_AZALEA,
			BlockTypeIds::HAY_BALE,
			BlockTypeIds::NETHER_WART_BLOCK,
			BlockTypeIds::PITCHER_PLANT,
			BlockTypeIds::RED_MUSHROOM_BLOCK,
			BlockTypeIds::TORCHFLOWER,
			BlockTypeIds::WARPED_WART_BLOCK => 85,
			BlockTypeIds::CAKE => 100,
			default => 0
		};
	}
}
