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

/**
 * Called when an effect is added to an Entity.
 */
class EntityEffectAddEvent extends EntityEffectEvent{
	public function __construct(Entity $entity, EffectInstance $effect, private ?EffectInstance $oldEffect = null){
		parent::__construct($entity, $effect);
	}

	/**
	 * Returns whether the effect addition will replace an existing effect already applied to the entity.
	 */
	public function willModify() : bool{
		return $this->hasOldEffect();
	}

	public function hasOldEffect() : bool{
		return $this->oldEffect instanceof EffectInstance;
	}

	public function getOldEffect() : ?EffectInstance{
		return $this->oldEffect;
	}
}
