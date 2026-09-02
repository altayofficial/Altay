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

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\world\particle\MaceGroundSmashParticle;
use pocketmine\world\particle\WindExplosionParticle;
use pocketmine\world\sound\MaceHeavySmashGroundSound;
use pocketmine\world\sound\MaceSmashAirSound;
use pocketmine\world\sound\MaceSmashGroundSound;
use function max;
use function min;

class Mace extends Tool{

	/** Damage dealt when the attacker did not fall far enough to smash. */
	public const BASE_ATTACK_POINTS = 6;
	/** Minimum fall distance required to turn an attack into a smash. */
	public const MINIMUM_SMASH_FALL_DISTANCE = 1.5;
	/** Damage above which a smash plays the heavy sound instead of the regular one. */
	public const HEAVY_SMASH_DAMAGE = 16.0;
	/** Damage below which a smash is not loud enough to play any sound. */
	public const SMASH_SOUND_DAMAGE = 7.0;
	/** Fall distance beyond which Wind Burst stops gaining extra launch power. */
	public const MAX_WIND_BURST_FALL_DISTANCE = 7.5;

	public function getMaxDurability() : int{
		return 500;
	}

	public function getAttackPoints() : int{
		return self::BASE_ATTACK_POINTS;
	}

	public function getEnchantability() : int{
		return 15;
	}

	public function onAttackEntity(Entity $victim, array &$returnedItems) : bool{
		return $this->applyDamage(1);
	}

	public function onDestroyBlock(Block $block, array &$returnedItems) : bool{
		if($block->getBreakInfo()->breaksInstantly()){
			return false;
		}

		return $this->applyDamage(2);
	}

	/**
	 * Returns whether the attacker fell far enough for their attack to count as a smash.
	 */
	public function canSmash(Entity $attacker) : bool{
		return $attacker->getFallDistance() >= self::MINIMUM_SMASH_FALL_DISTANCE && !$attacker->isUnderwater();
	}

	/**
	 * Returns the total attack damage of a smash performed after falling the given distance, before
	 * enchantment bonuses.
	 */
	public function getSmashDamage(float $fallDistance) : float{
		if($fallDistance < self::MINIMUM_SMASH_FALL_DISTANCE){
			return self::BASE_ATTACK_POINTS;
		}

		//the first 3 blocks of fall are worth 4 damage each, the next 5 are worth 2, the rest are worth 1
		$bonus = match(true){
			$fallDistance <= 3.0 => $fallDistance * 4,
			$fallDistance <= 8.0 => 12 + ($fallDistance - 3) * 2,
			default => 22 + ($fallDistance - 8)
		};

		return self::BASE_ATTACK_POINTS + $bonus;
	}

	/**
	 * Returns the extra damage granted by the Density enchantment for the given fall distance.
	 */
	public function getDensityBonus(float $fallDistance) : float{
		if($fallDistance < self::MINIMUM_SMASH_FALL_DISTANCE){
			return 0.0;
		}

		return $fallDistance * 0.5 * $this->getEnchantmentLevel(VanillaEnchantments::DENSITY());
	}

	/**
	 * Returns the fraction of the target's armour that remains effective against this mace, after the
	 * Breach enchantment. 1.0 means armour is fully effective.
	 */
	public function getArmorEfficiency() : float{
		$breachLevel = $this->getEnchantmentLevel(VanillaEnchantments::BREACH());

		return max(0.0, (100 - ($breachLevel * 15)) / 100);
	}

	/**
	 * Plays the impact sound and spawns the ground dust particles of a smash landed by the given attacker.
	 */
	public function playSmashEffects(Entity $attacker, float $damage) : void{
		if($damage < self::SMASH_SOUND_DAMAGE){
			return;
		}

		$sound = $damage >= self::HEAVY_SMASH_DAMAGE ? new MaceHeavySmashGroundSound() : new MaceSmashGroundSound();
		$world = $attacker->getWorld();
		$world->addSound($attacker->getPosition(), $sound);
		$world->addParticle($attacker->getPosition(), new MaceGroundSmashParticle());
	}

	/**
	 * Applies the Wind Burst enchantment, launching the attacker back upwards.
	 */
	public function applyWindBurst(Entity $attacker) : void{
		$level = $this->getEnchantmentLevel(VanillaEnchantments::WIND_BURST());
		if($level <= 0){
			return;
		}

		$fallDistance = $attacker->getFallDistance();
		if($fallDistance < self::MINIMUM_SMASH_FALL_DISTANCE){
			return;
		}

		$verticalBoost = 0.72 + min($fallDistance, self::MAX_WIND_BURST_FALL_DISTANCE) * 0.10 + match($level){
			2 => 0.55,
			3 => 1.3,
			default => 0.0
		};

		$attacker->setMotion($attacker->getMotion()->withComponents(null, $verticalBoost, null));

		$world = $attacker->getWorld();
		$world->addParticle($attacker->getPosition(), new WindExplosionParticle());
		$world->addSound($attacker->getPosition(), new MaceSmashAirSound());
	}
}
