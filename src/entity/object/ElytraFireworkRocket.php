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

use pocketmine\entity\Location;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class ElytraFireworkRocket extends FireworkRocket{

	/**
	 * @param FireworkRocketExplosion[] $explosions
	 */
	public function __construct(Location $location, int $maxFlightTimeTicks, array $explosions, private Player $boostedPlayer, ?CompoundTag $nbt = null){
		parent::__construct($location, $maxFlightTimeTicks, $explosions, $nbt);
	}

	public function getBoostedPlayer() : Player{
		return $this->boostedPlayer;
	}

	protected function tickFlight() : void{
		if(!$this->boostedPlayer->isConnected() || !$this->boostedPlayer->isAlive() || !$this->boostedPlayer->isGliding()){
			$this->flagForDespawn();
			return;
		}

		$location = $this->boostedPlayer->getLocation();
		$this->teleport($location, $location->getYaw(), $location->getPitch());
	}
}
