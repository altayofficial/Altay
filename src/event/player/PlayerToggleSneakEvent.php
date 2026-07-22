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

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerToggleSneakEvent extends PlayerEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		Player $player,
		protected bool $isSneaking,
		protected bool $isSneakPressed
	){
		$this->player = $player;
	}

	public function isSneaking() : bool{
		return $this->isSneaking;
	}

	/**
	 * Returns whether the player is pressing the sneak key.
	 * The player may still be sneaking even if this is false due to gameplay mechanics (e.g. releasing sneak while in a 1.5 block high space).
	 */
	public function isSneakPressed() : bool{
		return $this->isSneakPressed;
	}
}
