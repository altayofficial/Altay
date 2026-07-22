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

namespace pocketmine\entity\projectile;

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\particle\SnowballPoofParticle;

class Snowball extends Throwable{
	public static function getNetworkTypeId() : string{ return EntityIds::SNOWBALL; }

	protected function onHit(ProjectileHitEvent $event) : void{
		$world = $this->getWorld();
		for($i = 0; $i < 6; ++$i){
			$world->addParticle($this->location, new SnowballPoofParticle());
		}
	}
}
