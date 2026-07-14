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

use pocketmine\data\bedrock\NoteInstrumentIdMap;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class NoteSound implements Sound{
	public function __construct(
		private NoteInstrument $instrument,
		private int $note
	){
		if($this->note < 0 || $this->note > 255){
			throw new \InvalidArgumentException("Note $note is outside accepted range");
		}
	}

	public function encode(Vector3 $pos) : array{
		$instrumentId = NoteInstrumentIdMap::getInstance()->toId($this->instrument);
		return [LevelSoundEventPacket::nonActorSound(LevelSoundEvent::NOTE, $pos, false, ($instrumentId << 8) | $this->note)];
	}
}
