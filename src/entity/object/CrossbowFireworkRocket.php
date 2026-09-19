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

namespace pocketmine\entity\object;

use pocketmine\entity\Living;
use pocketmine\math\Vector3;

class CrossbowFireworkRocket extends FireworkRocket{

	/**
	 * Ticks during which the rocket cannot hit the entity which shot it, so that it doesn't explode in its face.
	 */
	private const OWNER_IMMUNITY_TICKS = 2;

	private ?Vector3 $lastMoveStart = null;

	protected function tickFlight() : void{
		//unlike a rocket launched from the ground, this one flies straight towards where the crossbow was aimed at
	}

	protected function move(float $dx, float $dy, float $dz) : void{
		$this->lastMoveStart = $this->location->asVector3();
		parent::move($dx, $dy, $dz);
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn() && $this->hitEntity() !== null){
			$this->flagForDespawn();
			$this->explode();
		}

		return $hasUpdate;
	}

	private function hitEntity() : ?Living{
		$owningEntityId = $this->getOwningEntityId();
		$end = $this->location->asVector3();
		$start = $this->lastMoveStart ?? $end;
		$delta = $end->subtractVector($start);
		$halfWidth = $this->getSize()->getWidth() / 2;

		foreach($this->getWorld()->getNearbyEntities($this->boundingBox->addCoord(-$delta->x, -$delta->y, -$delta->z), $this) as $entity){
			if(!$entity instanceof Living || !$entity->canBeCollidedWith()){
				continue;
			}

			if($entity->getId() === $owningEntityId && $this->ticksLived <= self::OWNER_IMMUNITY_TICKS){
				continue;
			}

			$entityBB = $entity->getBoundingBox()->expandedCopy($halfWidth, $halfWidth, $halfWidth);
			if($entityBB->isVectorInside($end) || $entityBB->calculateIntercept($start, $end) !== null){
				return $entity;
			}
		}

		return null;
	}
}
