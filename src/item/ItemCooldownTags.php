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

namespace pocketmine\item;

/**
 * Tags used by items to determine their cooldown group.
 *
 * These tag values are not related to Minecraft internal IDs.
 * They only share a visual similarity because these are the most obvious values to use.
 * Any arbitrary string can be used.
 *
 * @see Item::getCooldownTag()
 */
final class ItemCooldownTags{

	private function __construct(){
		//NOOP
	}

	public const CHORUS_FRUIT = "chorus_fruit";
	public const ENDER_PEARL = "ender_pearl";
	public const SHIELD = "shield";
	public const GOAT_HORN = "goat_horn";
}
