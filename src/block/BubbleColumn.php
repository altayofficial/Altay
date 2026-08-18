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
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\BubbleParticle;
use pocketmine\world\particle\SplashParticle;
use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;

class BubbleColumn extends Transparent{

	private const TICK_RATE = 5;

	private const DOWNWARD_MAX_MOTION = -0.3;
	private const DOWNWARD_MAX_EXIT_MOTION = -0.9;
	private const DOWNWARD_ACCELERATION = 0.03;
	private const UPWARD_MIN_MOTION = 0.7;
	private const UPWARD_MAX_EXIT_MOTION = 1.8;
	private const UPWARD_ACCELERATION = 0.08;
	private const UPWARD_EXIT_ACCELERATION = 0.1;

	protected bool $dragDown = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->dragDown);
	}

	public function isDragDown() : bool{
		return $this->dragDown;
	}

	/** @return $this */
	public function setDragDown(bool $dragDown) : self{
		$this->dragDown = $dragDown;
		return $this;
	}

	public function isSolid() : bool{
		return false;
	}

	public function canBeReplaced() : bool{
		return true;
	}

	public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock) : bool{
		return false;
	}

	protected function recalculateCollisionBoxes() : array{
		return [];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		if(!$entity->canBeMovedByCurrents()){
			return true;
		}

		$surface = $this->getSide(Facing::UP)->getTypeId() === BlockTypeIds::AIR;
		if($surface){
			$this->spawnColumnParticles();
		}

		if($entity instanceof Player){
			return true;
		}

		$motion = $entity->getMotion();
		$motionY = max($motion->y, self::DOWNWARD_MAX_MOTION);

		if($surface){
			$motionY = $this->dragDown
				? max(self::DOWNWARD_MAX_EXIT_MOTION, $motionY - self::DOWNWARD_ACCELERATION)
				: min(self::UPWARD_MAX_EXIT_MOTION, $motionY + self::UPWARD_EXIT_ACCELERATION);
		}else{
			$motionY = $this->dragDown
				? max(self::DOWNWARD_MAX_MOTION, $motionY - self::DOWNWARD_ACCELERATION)
				: min(self::UPWARD_MIN_MOTION, $motionY + self::UPWARD_ACCELERATION);
		}

		$entity->setMotion($motion->withComponents(null, $motionY, null));
		$entity->resetFallDistance();

		return true;
	}

	public function onNearbyBlockChange() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, self::TICK_RATE);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$down = $this->getSide(Facing::DOWN);

		$stillSupported = match(true){
			$down instanceof BubbleColumn => $down->dragDown === $this->dragDown,
			$down->getTypeId() === BlockTypeIds::MAGMA => $this->dragDown,
			$down->getTypeId() === BlockTypeIds::SOUL_SAND => !$this->dragDown,
			default => false
		};

		if(!$stillSupported){
			$world->setBlock($this->position, VanillaBlocks::WATER());
			return;
		}

		$up = $this->getSide(Facing::UP);
		if($up instanceof Water && $up->isSource()){
			$world->setBlock($up->position, (clone $this));
			$world->scheduleDelayedBlockUpdate($up->position, self::TICK_RATE);
		}
	}

	private function spawnColumnParticles() : void{
		$world = $this->position->getWorld();
		for($i = 0; $i < 2; $i++){
			$pos = $this->position->add(mt_rand() / mt_getrandmax(), 1 + mt_rand() / mt_getrandmax(), mt_rand() / mt_getrandmax());
			$world->addParticle($pos, new SplashParticle());
			$world->addParticle($pos, new BubbleParticle());
		}
	}
}
