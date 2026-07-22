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

use pocketmine\block\Block;
use pocketmine\entity\Entity;

/**
 * Called when an entity takes damage from a block.
 */
class EntityDamageByBlockEvent extends EntityDamageEvent{

	/**
	 * @param float[] $modifiers
	 */
	public function __construct(private Block $damager, Entity $entity, int $cause, float $damage, array $modifiers = []){
		parent::__construct($entity, $cause, $damage, $modifiers);
	}

	public function getDamager() : Block{
		return $this->damager;
	}
}
