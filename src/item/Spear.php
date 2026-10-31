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
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerSpearStabEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\CopperSpearAttackHitSound;
use pocketmine\world\sound\CopperSpearAttackMissSound;
use pocketmine\world\sound\CopperSpearUseSound;
use pocketmine\world\sound\DiamondSpearAttackHitSound;
use pocketmine\world\sound\DiamondSpearAttackMissSound;
use pocketmine\world\sound\DiamondSpearUseSound;
use pocketmine\world\sound\GoldenSpearAttackHitSound;
use pocketmine\world\sound\GoldenSpearAttackMissSound;
use pocketmine\world\sound\GoldenSpearUseSound;
use pocketmine\world\sound\IronSpearAttackHitSound;
use pocketmine\world\sound\IronSpearAttackMissSound;
use pocketmine\world\sound\IronSpearUseSound;
use pocketmine\world\sound\NetheriteSpearAttackHitSound;
use pocketmine\world\sound\NetheriteSpearAttackMissSound;
use pocketmine\world\sound\NetheriteSpearUseSound;
use pocketmine\world\sound\Sound;
use pocketmine\world\sound\SpearLungeSound;
use pocketmine\world\sound\StoneSpearAttackHitSound;
use pocketmine\world\sound\StoneSpearAttackMissSound;
use pocketmine\world\sound\StoneSpearUseSound;
use pocketmine\world\sound\WoodenSpearAttackHitSound;
use pocketmine\world\sound\WoodenSpearAttackMissSound;
use pocketmine\world\sound\WoodenSpearUseSound;
use function min;
use const PHP_FLOAT_MAX;

class Spear extends TieredTool implements Releasable{

	/** Minimum movement speed required for a charge sweep to actually connect. */
	public const MINIMUM_SPEED = 0.13;
	/** Minimum food level required to lunge, in gamemodes with finite resources. */
	public const MINIMUM_LUNGE_FOOD = 6;
	/** Exhaustion applied per level of Lunge. */
	public const BASE_LUNGE_EXHAUST = 4.0;
	/** How often (in ticks) the sweep check runs while the item is being held. */
	public const SWEEP_INTERVAL_TICKS = 5;
	/** Ticks a charge has to wait before it can hit the same entity again. */
	public const CHARGE_CONTACT_COOLDOWN_TICKS = 10;
	/** Maximum reach of a stab, in blocks. */
	public const MAX_STAB_DISTANCE = 5.0;

	public function getAttackPoints() : int{
		return $this->tier->getBaseAttackPoints();
	}

	public function getUsingTicks() : int{
		return 72000;
	}

	public function getMinUseDuration() : int{
		return 1;
	}

	public function canStartUsingItem(Player $player) : bool{
		return !$this->isBroken();
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

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		$player->getWorld()->addSound($player->getPosition(), $this->getUseSound());

		return ItemUseResult::NONE;
	}

	/**
	 * Jabs in the direction the player is looking. This is the spear's left click, and it's also what Lunge hooks
	 * into. Returns false if nothing happened, e.g. the spear is still cooling down.
	 */
	public function jab(Player $player) : bool{
		if($player->hasItemCooldown($this)){
			return false;
		}

		$event = new PlayerSpearStabEvent($player, $this, $player->getMovementSpeed());
		$event->call();
		if($event->isCancelled()){
			return false;
		}

		$player->resetItemCooldown($this, $this->getJabCooldownTicks());
		$this->applyLunge($player);

		$target = $this->findStabTarget($player);
		if($target !== null){
			$target->attack(new EntityDamageByEntityEvent(
				$player,
				$target,
				EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				$this->getJabDamage()
			));
			$player->getWorld()->addSound($player->getPosition(), $this->getHitSound());
		}else{
			$player->getWorld()->addSound($player->getPosition(), $this->getMissSound());
		}

		$this->applyDamage(1);

		return true;
	}

	/**
	 * Called every tick while the player holds the "use item" button, sweeping anything they charge into.
	 * Like vanilla, the charge only bites after a short wind-up and stops doing damage once it's been held too long.
	 */
	public function whileSpearUsing(Player $player) : void{
		$duration = $player->getItemUseDuration();
		[$delay, $damageTicks] = $this->getChargeTiming();
		if($duration < $delay || $duration > $delay + $damageTicks || $duration % self::SWEEP_INTERVAL_TICKS !== 0){
			return;
		}

		$speed = $player->getMovementSpeed();
		if($speed < self::MINIMUM_SPEED){
			return;
		}

		$target = $this->findSweepTarget($player);
		if($target === null){
			return;
		}

		$player->setSpearContactCooldown($target, self::CHARGE_CONTACT_COOLDOWN_TICKS);
		$target->attack(new EntityDamageByEntityEvent(
			$player,
			$target,
			EntityDamageEvent::CAUSE_ENTITY_ATTACK,
			$this->getAttackPoints() * 1.5 + ($speed * 3.0)
		));
		$player->getWorld()->addSound($player->getPosition(), $this->getHitSound());
	}

	/**
	 * Returns the damage dealt by a stab, including the bonus granted by Lunge.
	 */
	public function getJabDamage() : float{
		return $this->getAttackPoints() + ($this->getEnchantmentLevel(VanillaEnchantments::LUNGE()) * 1.5);
	}

	public function canLunge(Player $player) : bool{
		if($this->getEnchantmentLevel(VanillaEnchantments::LUNGE()) <= 0){
			return false;
		}

		if($player->isGliding() || $player->isSwimming() || $player->isUnderwater()){
			return false;
		}

		return !$player->hasFiniteResources() || $player->getHungerManager()->getFood() >= self::MINIMUM_LUNGE_FOOD;
	}

	public function applyLunge(Player $player) : void{
		if(!$this->canLunge($player)){
			return;
		}

		$direction = $player->getDirectionVector()->withComponents(null, 0, null);
		if($direction->lengthSquared() <= 0){
			return;
		}

		$lungeLevel = $this->getEnchantmentLevel(VanillaEnchantments::LUNGE());
		$push = $direction->normalize()->multiply(0.5 + ($lungeLevel * 0.4));

		$player->setMotion($player->getMotion()->addVector($push));
		$player->getWorld()->addSound($player->getPosition(), $this->getLungeSound($lungeLevel));
		$player->getHungerManager()->exhaust(self::BASE_LUNGE_EXHAUST * $lungeLevel, PlayerExhaustEvent::CAUSE_ATTACK);
	}

	/**
	 * Finds the best living entity inside the cone the player is looking at.
	 */
	private function findStabTarget(Player $player) : ?Living{
		$eyePos = $player->getEyePos();
		$direction = $player->getDirectionVector()->normalize();
		$searchBox = $player->getBoundingBox()->expandedCopy(self::MAX_STAB_DISTANCE, self::MAX_STAB_DISTANCE, self::MAX_STAB_DISTANCE);

		$target = null;
		$bestScore = -1.0;

		foreach($player->getWorld()->getNearbyEntities($searchBox, $player) as $entity){
			if(!$entity instanceof Living || !$entity->isAlive()){
				continue;
			}

			$targetPos = $entity->getPosition()->add(0, $entity->getEyeHeight() * 0.5, 0);
			$distance = $eyePos->distance($targetPos);
			if($distance > self::MAX_STAB_DISTANCE || $distance <= 0){
				continue;
			}

			$dot = $direction->dot($targetPos->subtractVector($eyePos)->normalize());
			if($dot < 0.866){
				continue;
			}

			$score = $dot - ($distance / self::MAX_STAB_DISTANCE) * 0.1;
			if($score <= $bestScore){
				continue;
			}

			$bestScore = $score;
			$target = $entity;
		}

		return $target;
	}

	/**
	 * Finds the closest living entity inside the box just in front of the player.
	 */
	private function findSweepTarget(Player $player) : ?Living{
		$direction = $player->getDirectionVector()->normalize()->multiply(1.5);
		$searchBox = $player->getBoundingBox()
			->expandedCopy(1.5, 1.0, 1.5)
			->offset($direction->x, $direction->y, $direction->z);

		$target = null;
		$closestDistance = PHP_FLOAT_MAX;

		foreach($player->getWorld()->getNearbyEntities($searchBox, $player) as $entity){
			if(!$entity instanceof Living || !$entity->isAlive() || $player->hasSpearContactCooldown($entity)){
				continue;
			}

			$distance = $entity->getPosition()->distanceSquared($player->getPosition());
			if($distance >= $closestDistance){
				continue;
			}

			$closestDistance = $distance;
			$target = $entity;
		}

		return $target;
	}

	/**
	 * Attack cooldown after a jab, from the vanilla item data for each tier.
	 */
	public function getJabCooldownTicks() : int{
		return match($this->tier){
			ToolTier::WOOD => 13,
			ToolTier::STONE => 15,
			ToolTier::COPPER => 17,
			ToolTier::IRON, ToolTier::GOLD => 19,
			ToolTier::DIAMOND => 21,
			ToolTier::NETHERITE => 23,
		};
	}

	/**
	 * Returns the wind-up before a charge can hit and how long it keeps doing damage after that, both in ticks.
	 *
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	private function getChargeTiming() : array{
		return match($this->tier){
			ToolTier::WOOD => [15, 300],
			ToolTier::STONE, ToolTier::GOLD => [14, 275],
			ToolTier::COPPER => [13, 250],
			ToolTier::IRON => [12, 225],
			ToolTier::DIAMOND => [10, 200],
			ToolTier::NETHERITE => [8, 175],
		};
	}

	private function getLungeSound(int $lungeLevel) : Sound{
		return new SpearLungeSound(min($lungeLevel, VanillaEnchantments::LUNGE()->getMaxLevel()));
	}

	private function getHitSound() : Sound{
		return match($this->tier){
			ToolTier::WOOD => new WoodenSpearAttackHitSound(),
			ToolTier::STONE => new StoneSpearAttackHitSound(),
			ToolTier::COPPER => new CopperSpearAttackHitSound(),
			ToolTier::IRON => new IronSpearAttackHitSound(),
			ToolTier::GOLD => new GoldenSpearAttackHitSound(),
			ToolTier::DIAMOND => new DiamondSpearAttackHitSound(),
			ToolTier::NETHERITE => new NetheriteSpearAttackHitSound(),
		};
	}

	private function getMissSound() : Sound{
		return match($this->tier){
			ToolTier::WOOD => new WoodenSpearAttackMissSound(),
			ToolTier::STONE => new StoneSpearAttackMissSound(),
			ToolTier::COPPER => new CopperSpearAttackMissSound(),
			ToolTier::IRON => new IronSpearAttackMissSound(),
			ToolTier::GOLD => new GoldenSpearAttackMissSound(),
			ToolTier::DIAMOND => new DiamondSpearAttackMissSound(),
			ToolTier::NETHERITE => new NetheriteSpearAttackMissSound(),
		};
	}

	private function getUseSound() : Sound{
		return match($this->tier){
			ToolTier::WOOD => new WoodenSpearUseSound(),
			ToolTier::STONE => new StoneSpearUseSound(),
			ToolTier::COPPER => new CopperSpearUseSound(),
			ToolTier::IRON => new IronSpearUseSound(),
			ToolTier::GOLD => new GoldenSpearUseSound(),
			ToolTier::DIAMOND => new DiamondSpearUseSound(),
			ToolTier::NETHERITE => new NetheriteSpearUseSound(),
		};
	}
}
