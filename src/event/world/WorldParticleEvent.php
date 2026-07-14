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

namespace pocketmine\event\world;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\World;

class WorldParticleEvent extends WorldEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @param Player[] $recipients
	 */
	public function __construct(
		World $world,
		private Particle $particle,
		private Vector3 $position,
		private array $recipients
	){
		parent::__construct($world);
	}

	public function getParticle() : Particle{
		return $this->particle;
	}

	public function setParticle(Particle $particle) : void{
		$this->particle = $particle;
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	/**
	 * @return Player[]
	 */
	public function getRecipients() : array{
		return $this->recipients;
	}

	/**
	 * @param Player[] $recipients
	 */
	public function setRecipients(array $recipients) : void{
		$this->recipients = $recipients;
	}
}
