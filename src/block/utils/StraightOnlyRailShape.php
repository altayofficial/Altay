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

enum StraightOnlyRailShape : int{
	case FLAT_AXIS_Z = RailShape::FLAT_AXIS_Z->value;
	case FLAT_AXIS_X = RailShape::FLAT_AXIS_X->value;
	case ASCENDING_EAST = RailShape::ASCENDING_EAST->value;
	case ASCENDING_WEST = RailShape::ASCENDING_WEST->value;
	case ASCENDING_NORTH = RailShape::ASCENDING_NORTH->value;
	case ASCENDING_SOUTH = RailShape::ASCENDING_SOUTH->value;
}
