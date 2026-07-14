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

namespace pocketmine\entity\animation;

use pocketmine\entity\projectile\Arrow;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class ArrowShakeAnimation implements Animation{

	public function __construct(
		private Arrow $arrow,
		private int $durationInTicks
	){}

	public function encode() : array{
		return [
			ActorEventPacket::create($this->arrow->getId(), ActorEvent::ARROW_SHAKE, $this->durationInTicks, null)
		];
	}
}
