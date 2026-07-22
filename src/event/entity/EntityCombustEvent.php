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

/**
 * @phpstan-extends EntityEvent<Entity>
 */
class EntityCombustEvent extends EntityEvent implements Cancellable{
	use CancellableTrait;

	protected int $duration;

	public function __construct(Entity $combustee, int $duration){
		$this->entity = $combustee;
		$this->duration = $duration;
	}

	/**
	 * Returns the duration in seconds the entity will burn for.
	 */
	public function getDuration() : int{
		return $this->duration;
	}

	public function setDuration(int $duration) : void{
		$this->duration = $duration;
	}
}
