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

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

/**
 * Played when an entity hits ground after falling a long distance (damage).
 * This is the bone-breaker "crunch" sound.
 */
class EntityLongFallSound implements Sound{
	public function __construct(private Entity $entity){}

	public function encode(Vector3 $pos) : array{
		return [LevelSoundEventPacket::create(
			LevelSoundEvent::FALL_BIG,
			$pos,
			-1,
			$this->entity::getNetworkTypeId(),
			false, //TODO: is isBaby relevant here?
			false,
			$this->entity->getId(),
			null
		)];
	}
}
