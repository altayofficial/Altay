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

namespace pocketmine\event\player;

use pocketmine\lang\Translatable;
use pocketmine\player\Player;

/**
 * Called when the player spawns in the world after logging in, when they first see the terrain.
 *
 * Note: A lot of data is sent to the player between login and this event. Disconnecting the player during this event
 * will cause this data to be wasted. Prefer disconnecting at login-time if possible to minimize bandwidth wastage.
 * @see PlayerLoginEvent
 */
class PlayerJoinEvent extends PlayerEvent{
	public function __construct(
		Player $player,
		protected Translatable|string $joinMessage
	){
		$this->player = $player;
	}

	public function setJoinMessage(Translatable|string $joinMessage) : void{
		$this->joinMessage = $joinMessage;
	}

	public function getJoinMessage() : Translatable|string{
		return $this->joinMessage;
	}
}
