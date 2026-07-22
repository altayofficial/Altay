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

namespace pocketmine\item\enchantment;

use pocketmine\entity\Entity;

/**
 * Classes extending this class can be applied to weapons and activate when used by a mob to attack another mob in melee
 * combat.
 */
abstract class MeleeWeaponEnchantment extends Enchantment{

	/**
	 * Returns whether this melee enchantment has an effect on the target entity. For example, Smite only applies to
	 * undead mobs.
	 */
	abstract public function isApplicableTo(Entity $victim) : bool;

	/**
	 * Returns the amount of additional damage caused by this enchantment to applicable targets.
	 */
	abstract public function getDamageBonus(int $enchantmentLevel) : float;

	/**
	 * Called after damaging the entity to apply any post damage effects to the target.
	 */
	public function onPostAttack(Entity $attacker, Entity $victim, int $enchantmentLevel) : void{

	}
}
