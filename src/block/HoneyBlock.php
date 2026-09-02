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

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use function abs;

class HoneyBlock extends Opaque{

	private const SLIDE_INSET = 0.1;
	private const SLIDE_MOTION = -0.05;

	public function getLightFilter() : int{
		return 1;
	}

	public function getFrictionFactor() : float{
		return 0.8;
	}

	public function getFallDamageMultiplier() : float{
		return 0.2;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::WEST, self::SLIDE_INSET)->trim(Facing::EAST, self::SLIDE_INSET)->trim(Facing::NORTH, self::SLIDE_INSET)->trim(Facing::SOUTH, self::SLIDE_INSET)];
	}

	public function onEntityInside(Entity $entity) : bool{
		$motion = $entity->getMotion();
		if($entity->isOnGround() || $motion->y > 0.08 || ($entity instanceof Player && $entity->isFlying())){
			return true;
		}

		$center = $this->position->add(0.5, 0, 0.5);
		$position = $entity->getPosition();
		$width = self::SLIDE_INSET + $entity->getSize()->getWidth() / 2;

		$ex = abs($center->x - $position->x);
		$ez = abs($center->z - $position->z);
		if($ex <= $width && $ez <= $width){
			return true;
		}

		$newMotionY = self::SLIDE_MOTION;
		$newMotionX = $motion->x;
		$newMotionZ = $motion->z;
		if($motion->y < -0.13){
			$multiplier = self::SLIDE_MOTION / $motion->y;
			$newMotionX *= $multiplier;
			$newMotionZ *= $multiplier;
		}

		$entity->setMotion($motion->withComponents($newMotionX, $newMotionY, $newMotionZ));
		$entity->resetFallDistance();

		return true;
	}
}
