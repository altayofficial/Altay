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

namespace pocketmine\world\particle;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use function abs;

class DragonEggTeleportParticle implements Particle{

	private int $xDiff;
	private int $yDiff;
	private int $zDiff;

	public function __construct(int $xDiff, int $yDiff, int $zDiff){
		$this->xDiff = self::boundOrThrow($xDiff);
		$this->yDiff = self::boundOrThrow($yDiff);
		$this->zDiff = self::boundOrThrow($zDiff);
	}

	private static function boundOrThrow(int $v) : int{
		if($v < -255 || $v > 255){
			throw new \InvalidArgumentException("Value must be between -255 and 255");
		}
		return $v;
	}

	public function encode(Vector3 $pos) : array{
		$data = ($this->zDiff < 0 ? 1 << 26 : 0) |
			($this->yDiff < 0 ? 1 << 25 : 0) |
			($this->xDiff < 0 ? 1 << 24 : 0) |
			(abs($this->xDiff) << 16) |
			(abs($this->yDiff) << 8) |
			abs($this->zDiff);

		return [LevelEventPacket::create(LevelEvent::PARTICLE_DRAGON_EGG_TELEPORT, $data, $pos)];
	}
}
