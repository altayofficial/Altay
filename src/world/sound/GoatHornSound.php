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

use pocketmine\item\GoatHornType;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class GoatHornSound implements Sound{
	public function __construct(private GoatHornType $goatHornType){}

	public function encode(Vector3 $pos) : array{
		return [LevelSoundEventPacket::nonActorSound(match($this->goatHornType){
			GoatHornType::PONDER => LevelSoundEvent::HORN_CALL0,
			GoatHornType::SING => LevelSoundEvent::HORN_CALL1,
			GoatHornType::SEEK => LevelSoundEvent::HORN_CALL2,
			GoatHornType::FEEL => LevelSoundEvent::HORN_CALL3,
			GoatHornType::ADMIRE => LevelSoundEvent::HORN_CALL4,
			GoatHornType::CALL => LevelSoundEvent::HORN_CALL5,
			GoatHornType::YEARN => LevelSoundEvent::HORN_CALL6,
			GoatHornType::DREAM => LevelSoundEvent::HORN_CALL7
		}, $pos, false)];
	}
}
