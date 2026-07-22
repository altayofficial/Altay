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

namespace pocketmine\event\entity;

use pocketmine\entity\projectile\Projectile;
use pocketmine\math\RayTraceResult;

/**
 * @allowHandle
 * @phpstan-extends EntityEvent<Projectile>
 */
abstract class ProjectileHitEvent extends EntityEvent{
	public function __construct(
		Projectile $entity,
		private RayTraceResult $rayTraceResult
	){
		$this->entity = $entity;
	}

	/**
	 * @return Projectile
	 */
	public function getEntity(){
		return $this->entity;
	}

	/**
	 * Returns a RayTraceResult object containing information such as the exact position struck, the AABB it hit, and
	 * the face of the AABB that it hit.
	 */
	public function getRayTraceResult() : RayTraceResult{
		return $this->rayTraceResult;
	}
}
