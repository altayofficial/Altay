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

namespace pocketmine\world\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class SpearLungeSound implements Sound{

	public function __construct(private int $level){
	}

	public function encode(Vector3 $pos) : array{
		$sound = match($this->level){
			1 => LevelSoundEvent::ITEM_ENCHANT_LUNGE1,
			2 => LevelSoundEvent::ITEM_ENCHANT_LUNGE2,
			default => LevelSoundEvent::ITEM_ENCHANT_LUNGE3,
		};

		return [LevelSoundEventPacket::nonActorSound($sound, $pos, false)];
	}
}
