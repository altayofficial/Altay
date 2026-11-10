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

namespace pocketmine\inventory\transaction\action\filter;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;

/**
 * Decides whether a player may make a particular slot change. Unlike a SlotValidator this isn't about whether the
 * item fits, it's a policy call, so refusing just cancels the transaction instead of flagging it as invalid.
 */
interface SlotChangeFilter{
	/**
	 * Returns false to cancel the transaction this action belongs to.
	 *
	 * This runs while the transaction is still being processed, so don't open or close windows from here.
	 * Do that on the next tick instead.
	 */
	public function accepts(Player $player, SlotChangeAction $action) : bool;
}
