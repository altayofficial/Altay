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

use pocketmine\block\utils\DripstoneThickness;
use pocketmine\block\utils\Fallable;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\Location;
use pocketmine\entity\object\FallingBlock;
use pocketmine\item\Item;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\sound\CauldronDripLavaPointedDripstoneSound;
use pocketmine\world\sound\CauldronDripWaterPointedDripstoneSound;
use pocketmine\world\sound\Sound;
use pocketmine\world\World;
use function min;
use function mt_getrandmax;
use function mt_rand;

class PointedDripstone extends Transparent implements Fallable{
	/** Chance per random tick that a tip grows by one block, matching vanilla's growth rate. */
	private const GROWTH_CHANCE = 0.011377778;
	/** Chance per random tick that a hanging tip drips into a cauldron below it, if fed by a fluid above. */
	private const LAVA_DRIP_CHANCE = 31.0 / 256.0;
	private const WATER_DRIP_CHANCE = 92.0 / 256.0;
	private const MAX_DRIP_SEARCH_DISTANCE = 11;
	private const MAX_HEIGHT = 7;

	protected DripstoneThickness $thickness = DripstoneThickness::TIP;
	protected bool $hanging = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->thickness);
		$w->bool($this->hanging);
	}

	public function getThickness() : DripstoneThickness{
		return $this->thickness;
	}

	/** @return $this */
	public function setThickness(DripstoneThickness $thickness) : self{
		$this->thickness = $thickness;
		return $this;
	}

	public function isHanging() : bool{
		return $this->hanging;
	}

	/** @return $this */
	public function setHanging(bool $hanging) : self{
		$this->hanging = $hanging;
		return $this;
	}

	public function getDropsForIncompatibleTool(Item $item) : array{
		return [$this->asItem()];
	}

	public function ticksRandomly() : bool{
		return $this->thickness === DripstoneThickness::TIP;
	}

	protected function recalculateCollisionBoxes() : array{
		if($this->thickness === DripstoneThickness::TIP){
			$box = AxisAlignedBB::one()->squash(Axis::X, 6 / 16)->squash(Axis::Z, 6 / 16);
			return [$this->hanging ? $box->trim(Facing::DOWN, 5 / 16) : $box->trim(Facing::UP, 5 / 16)];
		}
		return [AxisAlignedBB::one()->squash(Axis::X, 2 / 16)->squash(Axis::Z, 2 / 16)];
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$world = $blockReplace->getPosition()->getWorld();
		$pos = $blockReplace->getPosition();
		$above = $world->getBlock($pos->up());
		$below = $world->getBlock($pos->down());
		if($above->getTypeId() === BlockTypeIds::AIR && $below->getTypeId() === BlockTypeIds::AIR){
			//needs something to anchor to, either above (hanging) or below (standing) - clicking the side of an
			//isolated block with nothing above/below it isn't a valid placement
			return false;
		}
		$aboveIsSame = $above instanceof PointedDripstone;
		$belowIsSame = $below instanceof PointedDripstone;

		if($aboveIsSame && $belowIsSame){
			$this->thickness = DripstoneThickness::MERGE;
			$this->hanging = false;
			$tx->addBlock($pos->up(), (clone $above)->setThickness(DripstoneThickness::MERGE));
		}elseif($aboveIsSame){
			if($below->getTypeId() !== BlockTypeIds::AIR && $face === Facing::UP){
				$this->thickness = DripstoneThickness::MERGE;
				$this->hanging = false;
				$tx->addBlock($pos->up(), (clone $above)->setThickness(DripstoneThickness::MERGE));
			}else{
				$this->thickness = DripstoneThickness::TIP;
				$this->hanging = true;
			}
		}elseif($belowIsSame){
			if($above->getTypeId() !== BlockTypeIds::AIR && $face === Facing::DOWN){
				$this->thickness = DripstoneThickness::MERGE;
				$this->hanging = true;
				$tx->addBlock($pos->down(), (clone $below)->setThickness(DripstoneThickness::MERGE));
			}else{
				$this->thickness = DripstoneThickness::TIP;
				$this->hanging = false;
			}
		}else{
			$this->thickness = DripstoneThickness::TIP;
			$this->hanging = $face !== Facing::UP;
		}

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onPostPlace() : void{
		if($this->thickness === DripstoneThickness::MERGE){
			//merging into an existing chain doesn't change the taper of the segments around it
			return;
		}
		self::updateTaper($this->position->getWorld(), $this->position, $this->hanging);
	}

	/**
	 * Re-tapers the 1-2 segments between a newly placed/grown tip and the rest of its stalactite/stalagmite chain
	 * (if any), matching vanilla's frustum/base/middle progression.
	 */
	private static function updateTaper(World $world, Vector3 $tipPos, bool $hanging) : void{
		$towardAnchor = $hanging ? Facing::UP : Facing::DOWN;

		$length = 0;
		$pos = $tipPos->getSide($towardAnchor);
		while($length < self::MAX_DRIP_SEARCH_DISTANCE && $world->getBlock($pos) instanceof PointedDripstone){
			$length++;
			$pos = $pos->getSide($towardAnchor);
		}
		if($length === 0){
			return;
		}

		$frustumPos = $tipPos->getSide($towardAnchor);
		$frustumBlock = $world->getBlock($frustumPos);
		if($frustumBlock instanceof PointedDripstone && $frustumBlock->getThickness() !== DripstoneThickness::MERGE){
			$world->setBlock($frustumPos, (clone $frustumBlock)->setThickness(DripstoneThickness::FRUSTUM));
		}

		if($length >= 2){
			$secondPos = $frustumPos->getSide($towardAnchor);
			$secondBlock = $world->getBlock($secondPos);
			if($secondBlock instanceof PointedDripstone && $secondBlock->getThickness() !== DripstoneThickness::MERGE){
				$world->setBlock($secondPos, (clone $secondBlock)->setThickness($length === 2 ? DripstoneThickness::BASE : DripstoneThickness::MIDDLE));
			}
		}
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		$supportPos = $this->hanging ? $this->position->up() : $this->position->down();
		$support = $world->getBlock($supportPos);
		if($support instanceof PointedDripstone && $support->isHanging() === $this->hanging){
			return; //still attached to the rest of the stalactite/stalagmite chain
		}
		if($support->getTypeId() !== BlockTypeIds::AIR){
			return; //still supported
		}

		//TODO: better method?
		$checkPos = new Vector3($this->position->getX(), $this->position->getY() - 1, $this->position->getZ());
		if($world->getBlock($checkPos)->getTypeId() !== BlockTypeIds::AIR){
			//1 block or less to fall - just break instantly instead of spawning a FallingBlock for it
			$world->useBreakOn($this->position);
			return;
		}

		$world->setBlock($this->position, VanillaBlocks::AIR());
		(new FallingBlock(Location::fromObject($this->position->add(0.5, 0, 0.5), $world), $this))->spawnToAll();
	}

	public function tickFalling() : ?Block{
		return null;
	}

	public function onHitGround(FallingBlock $blockEntity) : bool{
		//a fallen stalactite/stalagmite always shatters into a dropped item rather than replanting itself
		$blockEntity->getWorld()->dropItem($blockEntity->getPosition(), $this->asItem());
		return false;
	}

	public function getFallDamagePerBlock() : float{
		return 2.0;
	}

	public function getMaxFallDamage() : float{
		return 40.0;
	}

	public function getLandSound() : ?Sound{
		return null;
	}

	public function getFallDamage(float $vanillaFallDamage, float $fallDistance, int $jumpBoostLevel) : float{
		if($this->hanging || $this->thickness !== DripstoneThickness::TIP){
			return parent::getFallDamage($vanillaFallDamage, $fallDistance, $jumpBoostLevel);
		}
		$damage = ($fallDistance - $jumpBoostLevel) * 2 - 1;
		return $damage > 0 ? $damage : 0.0;
	}

	public function onRandomTick() : void{
		if($this->thickness !== DripstoneThickness::TIP){
			return;
		}
		if(mt_rand() / mt_getrandmax() <= self::GROWTH_CHANCE){
			$this->grow();
		}
		$this->dripIntoCauldron();
	}

	private function grow() : void{
		$world = $this->position->getWorld();
		$growthFace = $this->hanging ? Facing::DOWN : Facing::UP;
		$targetPos = $this->position->getSide($growthFace);
		if($world->getBlock($targetPos)->getTypeId() !== BlockTypeIds::AIR){
			return;
		}
		if(self::getChainLength($world, $this->position, $this->hanging) >= self::MAX_HEIGHT){
			return;
		}

		$world->setBlock($targetPos, VanillaBlocks::POINTED_DRIPSTONE()->setThickness(DripstoneThickness::TIP)->setHanging($this->hanging));
		self::updateTaper($world, $targetPos, $this->hanging);
	}

	/**
	 * Counts how many contiguous {@link PointedDripstone} blocks (including the tip at $tipPos) make up the
	 * stalactite/stalagmite chain, i.e. its current height.
	 */
	private static function getChainLength(World $world, Vector3 $tipPos, bool $hanging) : int{
		$towardAnchor = $hanging ? Facing::UP : Facing::DOWN;
		$length = 1;
		$pos = $tipPos->getSide($towardAnchor);
		while($length < self::MAX_HEIGHT && $world->getBlock($pos) instanceof PointedDripstone){
			$length++;
			$pos = $pos->getSide($towardAnchor);
		}
		return $length;
	}

	private function dripIntoCauldron() : void{
		if(!$this->hanging){
			return;
		}
		$world = $this->position->getWorld();
		$above = $world->getBlock($this->position->up());
		if(!($above instanceof Water) && !($above instanceof Lava)){
			return;
		}

		$pos = $this->position->down();
		$distance = 0;
		while($distance < self::MAX_DRIP_SEARCH_DISTANCE && $world->getBlock($pos)->getTypeId() === BlockTypeIds::AIR){
			$pos = $pos->down();
			$distance++;
		}

		$below = $world->getBlock($pos);
		$roll = mt_rand() / mt_getrandmax();
		if($above instanceof Lava){
			if($roll > self::LAVA_DRIP_CHANCE){
				return;
			}
			if($below instanceof Cauldron){
				$world->setBlock($pos, VanillaBlocks::LAVA_CAULDRON()->setFillLevel(FillableCauldron::MIN_FILL_LEVEL));
			}elseif($below instanceof LavaCauldron && $below->getFillLevel() < FillableCauldron::MAX_FILL_LEVEL){
				$world->setBlock($pos, (clone $below)->setFillLevel(min(FillableCauldron::MAX_FILL_LEVEL, $below->getFillLevel() + 1)));
			}else{
				return;
			}
			$world->addSound($this->position->add(0.5, 1, 0.5), new CauldronDripLavaPointedDripstoneSound());
		}else{
			if($roll > self::WATER_DRIP_CHANCE){
				return;
			}
			if($below instanceof Cauldron){
				$world->setBlock($pos, VanillaBlocks::WATER_CAULDRON()->setFillLevel(FillableCauldron::MIN_FILL_LEVEL));
			}elseif($below instanceof WaterCauldron && $below->getFillLevel() < FillableCauldron::MAX_FILL_LEVEL){
				$world->setBlock($pos, (clone $below)->setFillLevel(min(FillableCauldron::MAX_FILL_LEVEL, $below->getFillLevel() + 1)));
			}else{
				return;
			}
			$world->addSound($this->position->add(0.5, 1, 0.5), new CauldronDripWaterPointedDripstoneSound());
		}
	}
}
