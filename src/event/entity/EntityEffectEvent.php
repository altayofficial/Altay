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

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

/**
 * @phpstan-extends EntityEvent<Entity>
 */
class EntityEffectEvent extends EntityEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		Entity $entity,
		private EffectInstance $effect
	){
		$this->entity = $entity;
	}

	public function getEffect() : EffectInstance{
		return $this->effect;
	}
}
