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
 * @method static SupportType CENTER()
 * @method static SupportType EDGE()
 * @method static SupportType FULL()
 * @method static SupportType NONE()
 */
enum SupportType{
	use LegacyEnumShimTrait;

	case FULL;
	case CENTER;
	case EDGE;
	case NONE;

	public static function fromShape(VoxelShape $shape, int $facing) : self{
		if($shape->isFaceFull($facing)){
			return self::FULL;
		}

		$inner = 2 / 16;
		$outer = 14 / 16;
		if(
			$shape->coversFace($facing, 0, 0, 1, $inner) &&
			$shape->coversFace($facing, 0, $outer, 1, 1) &&
			$shape->coversFace($facing, 0, $inner, $inner, $outer) &&
			$shape->coversFace($facing, $outer, $inner, 1, $outer)
		){
			return self::EDGE;
		}

		if($shape->coversFace($facing, 7 / 16, 7 / 16, 9 / 16, 9 / 16)){
			return self::CENTER;
		}

		return self::NONE;
	}

	public function hasEdgeSupport() : bool{
		return $this === self::EDGE || $this === self::FULL;
	}

	public function hasCenterSupport() : bool{
		return $this === self::CENTER || $this === self::FULL;
	}
}
