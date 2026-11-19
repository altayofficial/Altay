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

namespace pocketmine\player;

/**
 * Things a player may or may not be allowed to do. Each game mode has its own defaults (see
 * {@link GameMode::hasAbility()}), and plugins can override them per player with {@link Player::setAbility()}.
 */
enum PlayerAbility{
	/** Placing blocks. */
	case BUILD;
	/** Breaking blocks. */
	case MINE;
	case ATTACK_PLAYERS;
	case ATTACK_MOBS;
	/** Items aren't used up, picked blocks are given for free, and blocks don't drop items when broken. */
	case INFINITE_RESOURCES;
	/** Immune to all damage except suicide (e.g. /kill). */
	case INVULNERABLE;
}
