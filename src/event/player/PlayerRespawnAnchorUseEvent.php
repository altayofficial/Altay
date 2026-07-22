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

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerRespawnAnchorUseEvent extends PlayerEvent implements Cancellable{
	use CancellableTrait;

	public const ACTION_EXPLODE = 0;
	public const ACTION_SET_SPAWN = 1;

	public function __construct(
		Player $player,
		protected Block $block,
		private int $action = self::ACTION_EXPLODE
	){
		$this->player = $player;
	}

	public function getBlock() : Block{
		return $this->block;
	}

	public function getAction() : int{
		return $this->action;
	}

	public function setAction(int $action) : void{
		$this->action = $action;
	}
}
