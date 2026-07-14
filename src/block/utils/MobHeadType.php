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

use pocketmine\utils\LegacyEnumShimTrait;

/**
 * TODO: These tags need to be removed once we get rid of LegacyEnumShimTrait (PM6)
 *  These are retained for backwards compatibility only.
 *
 * @method static MobHeadType CREEPER()
 * @method static MobHeadType DRAGON()
 * @method static MobHeadType PIGLIN()
 * @method static MobHeadType PLAYER()
 * @method static MobHeadType SKELETON()
 * @method static MobHeadType WITHER_SKELETON()
 * @method static MobHeadType ZOMBIE()
 */
enum MobHeadType{
	use LegacyEnumShimTrait;

	case SKELETON;
	case WITHER_SKELETON;
	case ZOMBIE;
	case PLAYER;
	case CREEPER;
	case DRAGON;
	case PIGLIN;

	public function getDisplayName() : string{
		return match($this){
			self::SKELETON => "Skeleton Skull",
			self::WITHER_SKELETON => "Wither Skeleton Skull",
			self::ZOMBIE => "Zombie Head",
			self::PLAYER => "Player Head",
			self::CREEPER => "Creeper Head",
			self::DRAGON => "Dragon Head",
			self::PIGLIN => "Piglin Head"
		};
	}
}
