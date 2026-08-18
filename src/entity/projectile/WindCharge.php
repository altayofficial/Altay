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

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\block\Button;
use pocketmine\block\CakeWithCandle;
use pocketmine\block\Candle;
use pocketmine\block\ChorusFlower;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Lever;
use pocketmine\block\Trapdoor;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\particle\WindExplosionParticle;
use pocketmine\world\sound\WindChargeBurstSound;

class WindCharge extends Throwable{
	private const BURST_RADIUS = 2.0;
	private const KNOCKBACK_STRENGTH = 0.2;
	private const MAX_AGE = 1200;

	protected float $damage = 1.0;

	public static function getNetworkTypeId() : string{ return EntityIds::WIND_CHARGE_PROJECTILE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.3125, 0.3125); }

	protected function getInitialGravity() : float{ return 0.0; }

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->ticksLived > self::MAX_AGE){
			$this->flagForDespawn();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	protected function onHit(ProjectileHitEvent $event) : void{
		$position = $event->getRayTraceResult()->getHitVector();
		$world = $this->getWorld();

		$world->addParticle($position, new WindExplosionParticle());
		$world->addSound($position->add(0, 1, 0), new WindChargeBurstSound());

		$radius = self::BURST_RADIUS;
		foreach($world->getNearbyEntities($this->boundingBox->expandedCopy($radius, $radius, $radius), $this) as $entity){
			if($entity instanceof Living && $entity->getPosition()->distance($position) < $radius){
				$this->knockBack($entity);
			}
		}
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);

		if(
			$blockHit instanceof Door ||
			$blockHit instanceof Trapdoor ||
			$blockHit instanceof FenceGate ||
			$blockHit instanceof Button ||
			$blockHit instanceof Lever ||
			$blockHit instanceof Candle ||
			$blockHit instanceof CakeWithCandle
		){
			$blockHit->onInteract(VanillaItems::AIR(), $hitResult->getHitFace(), $hitResult->getHitVector());
		}elseif($blockHit instanceof ChorusFlower){
			$this->getWorld()->useBreakOn($blockHit->getPosition()); // wtf, horrible idea mojang
		}
	}

	private function knockBack(Entity $entity) : void{
		$motion = $entity->getMotion();
		$entityPos = $entity->getPosition();

		$entity->setMotion(new Vector3(
			($motion->x / 2) - (($this->location->x - $entityPos->x) * self::KNOCKBACK_STRENGTH),
			($motion->y / 2) + 0.6,
			($motion->z / 2) - (($this->location->z - $entityPos->z) * self::KNOCKBACK_STRENGTH)
		));
	}
}
