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

namespace pocketmine\block\utils;


enum MushroomBlockType{
	case PORES;
	case CAP_NORTHWEST;
	case CAP_NORTH;
	case CAP_NORTHEAST;
	case CAP_WEST;
	case CAP_MIDDLE;
	case CAP_EAST;
	case CAP_SOUTHWEST;
	case CAP_SOUTH;
	case CAP_SOUTHEAST;
	case ALL_CAP;
}
