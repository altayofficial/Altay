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


enum NoteInstrument{
	case PIANO;
	case BASS_DRUM;
	case SNARE;
	case CLICKS_AND_STICKS;
	case DOUBLE_BASS;
	case BELL;
	case FLUTE;
	case CHIME;
	case GUITAR;
	case XYLOPHONE;
	case IRON_XYLOPHONE;
	case COW_BELL;
	case DIDGERIDOO;
	case BIT;
	case BANJO;
	case PLING;
}
