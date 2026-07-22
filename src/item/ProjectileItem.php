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

namespace pocketmine\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;

abstract class ProjectileItem extends Item{

	abstract public function getThrowForce() : float;

	abstract protected function createEntity(Location $location, Player $thrower) : Throwable;

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		$location = $player->getLocation();

		$projectile = $this->createEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
		$projectile->setMotion($directionVector->multiply($this->getThrowForce()));

		$projectileEv = new ProjectileLaunchEvent($projectile);
		$projectileEv->call();
		if($projectileEv->isCancelled()){
			$projectile->flagForDespawn();
			return ItemUseResult::FAIL;
		}

		$projectile->spawnToAll();

		$location->getWorld()->addSound($location, new ThrowSound());

		$this->pop();

		return ItemUseResult::SUCCESS;
	}
}
