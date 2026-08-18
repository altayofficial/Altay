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
use pocketmine\math\Vector3;
use pocketmine\world\particle\MaceGroundSmashParticle;
use pocketmine\world\particle\WindExplosionParticle;
use pocketmine\world\sound\MaceHeavySmashGroundSound;
use pocketmine\world\sound\MaceSmashAirSound;
use pocketmine\world\sound\MaceSmashGroundSound;
use function cos;
use function deg2rad;
use function floor;
use function max;
use function min;
use function sin;

class Mace extends Tool{

	/** Damage dealt when the attacker did not fall far enough to smash. */
	public const BASE_ATTACK_POINTS = 6;
	/** Minimum fall distance required to turn an attack into a smash. */
	public const MINIMUM_SMASH_FALL_DISTANCE = 1.5;
	/** Damage above which a smash plays the heavy sound instead of the regular one. */
	public const HEAVY_SMASH_DAMAGE = 16.0;
	/** Damage below which a smash is not loud enough to play any sound. */
	public const SMASH_SOUND_DAMAGE = 7.0;

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
		return $attacker->getFallDistance() >= self::MINIMUM_SMASH_FALL_DISTANCE;
	}

	/**
	 * Returns the total attack damage of a smash performed after falling the given distance, before
	 * enchantment bonuses.
	 */
	public function getSmashDamage(float $fallDistance) : float{
		if($fallDistance < self::MINIMUM_SMASH_FALL_DISTANCE){
			return self::BASE_ATTACK_POINTS;
		}

		$blocks = (int) floor($fallDistance);
		$damage = 0;
		for($i = 0; $i <= $blocks; $i++){
			if($i < 3){
				$damage += 4;
			}elseif($i < 8){
				$damage += 2;
			}else{
				$damage++;
			}
		}

		return $damage;
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

	public function playSmashSound(Entity $victim, float $damage) : void{
		if($damage < self::SMASH_SOUND_DAMAGE){
			return;
		}

		$sound = $damage >= self::HEAVY_SMASH_DAMAGE ? new MaceHeavySmashGroundSound() : new MaceSmashGroundSound();
		$victim->getWorld()->addSound($victim->getPosition(), $sound);
		$victim->getWorld()->addParticle($victim->getPosition(), new MaceGroundSmashParticle());
	}

	/**
	 * Applies the Wind Burst enchantment: launches the attacker upwards and forwards, pushes nearby
	 * entities away and spawns wind explosion particles.
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

		$clampedFall = min($fallDistance, 7.5);
		$verticalBoost = 0.72 + $clampedFall * 0.10 + match($level){
			2 => 0.55,
			3 => 1.3,
			default => 0.0
		};

		$yaw = deg2rad($attacker->getLocation()->yaw);
		$forward = (new Vector3(-sin($yaw), 0, cos($yaw)))->normalize();
		$forwardBoost = 0.08 + 0.02 * $level;

		$attacker->resetFallDistance();
		$attacker->setMotion($forward->multiply($forwardBoost)->withComponents(null, $verticalBoost, null));

		$world = $attacker->getWorld();
		$searchBox = $attacker->getBoundingBox()->expandedCopy(2.5, 2.5, 2.5);
		foreach($world->getNearbyEntities($searchBox, $attacker) as $entity){
			$direction = $entity->getPosition()->subtractVector($attacker->getPosition());
			if($direction->lengthSquared() <= 0){
				continue;
			}

			$gust = $direction->normalize()->multiply(0.6 + 0.15 * $level);
			$pushed = $entity->getMotion()->addVector($gust);
			$entity->setMotion($pushed->withComponents(null, max($pushed->y, 0.4 + 0.1 * $level), null));

			$world->addParticle($entity->getPosition()->add(0, $entity->getEyeHeight() * 0.6, 0), new WindExplosionParticle());
		}

		$eyePos = $attacker->getPosition()->add(0, $attacker->getEyeHeight() * 0.6, 0);
		$world->addParticle($eyePos, new WindExplosionParticle());
		$world->addSound($attacker->getPosition(), new MaceSmashAirSound());
	}
}
