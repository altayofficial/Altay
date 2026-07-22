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

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\utils\Utils;
use pocketmine\world\Position;

/**
 * @phpstan-extends EntityEvent<Entity>
 */
class EntityTeleportEvent extends EntityEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		Entity $entity,
		private Position $from,
		private Position $to
	){
		$this->entity = $entity;
	}

	public function getFrom() : Position{
		return $this->from;
	}

	public function getTo() : Position{
		return $this->to;
	}

	public function setTo(Position $to) : void{
		Utils::checkVector3NotInfOrNaN($to);
		$this->to = $to;
	}
}
