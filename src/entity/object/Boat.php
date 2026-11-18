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

namespace pocketmine\entity\object;

use pocketmine\block\Water;
use pocketmine\data\bedrock\BoatTypeIdMap;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\BoatType;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use function count;
use function floor;
use function fmod;
use function max;
use function min;
use function spl_object_id;

class Boat extends Entity{
	public const TAG_VARIANT = "Variant"; //TAG_Int

	public const HEIGHT = 0.455;
	public const WIDTH = 1.4;

	private const MAX_PASSENGERS = 2;

	/**
	 * The client draws a boat this far above where it actually is, and the positions it sends back for a boat
	 * it's driving are raised by the same amount
	 */
	private const NETWORK_OFFSET = 0.375;

	/** How far below the water surface the bottom of a floating boat settles. */
	private const FLOAT_DEPTH = 0.385;

	private const HURT_ANIMATION_TICKS = 10;

	private const PADDLE_SPEED = 0.04;

	private const BUOYANCY_DATA = '{"apply_gravity":true,"base_buoyancy":1.0,"big_wave_probability":0.03,"big_wave_speed":10.0,"can_auto_step_from_liquid":false,"drag_down_on_buoyancy_removed":0.0,"liquid_blocks":["minecraft:water","minecraft:flowing_water"],"movement_type":"waves"}';

	public static function getNetworkTypeId() : string{ return "minecraft:boat"; }

	private int $hurtTime = 0;
	private int $hurtDirection = 1;

	private float $paddleTimeLeft = 0.0;
	private float $paddleTimeRight = 0.0;

	public function __construct(
		Location $location,
		private BoatType $boatType,
		?CompoundTag $nbt = null
	){
		parent::__construct($location, $nbt);
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(self::HEIGHT, self::WIDTH); }

	protected function getInitialDragMultiplier() : float{ return 0.02; }

	protected function getInitialGravity() : float{ return 0.04; }

	public function getBoatType() : BoatType{ return $this->boatType; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$this->setMaxHealth(40);
		$this->setHealth(40);
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setInt(self::TAG_VARIANT, BoatTypeIdMap::getInstance()->toId($this->boatType));

		return $nbt;
	}

	/**
	 * Returns the passenger steering the boat, which is whoever got in first, as long as it's a player.
	 */
	public function getControllingPassenger() : ?Player{
		$driver = $this->getPassengers()[0] ?? null;
		return $driver instanceof Player ? $driver : null;
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($player->getVehicle() !== null){
			return false;
		}
		return $this->addPassenger($player);
	}

	public function addPassenger(Entity $passenger) : bool{
		if(count($this->getPassengers()) >= self::MAX_PASSENGERS || !parent::addPassenger($passenger)){
			return false;
		}
		$this->markPassengerSeatsDirty();

		return true;
	}

	public function removePassenger(Entity $passenger) : bool{
		if(!parent::removePassenger($passenger)){
			return false;
		}
		$this->markPassengerSeatsDirty();

		//the client was driving it until now, so the server has to pick the physics back up from wherever it was left
		$this->setForceMovementUpdate();
		$this->scheduleUpdate();

		return true;
	}

	/**
	 * Seats depend on how many riders there are, so everyone gets moved when someone gets in or out.
	 */
	private function markPassengerSeatsDirty() : void{
		foreach($this->getPassengers() as $passenger){
			$passenger->networkPropertiesDirty = true;
		}
	}

	/**
	 * A lone rider sits in the middle. With two, the driver moves forward and the other one sits at the back.
	 * These are in the boat's own frame, the client turns them with the boat.
	 */
	public function getSeatPosition(?Entity $passenger = null) : Vector3{
		$passengers = $this->getPassengers();
		if(count($passengers) < 2){
			return new Vector3(0, 1.02, 0);
		}

		return new Vector3($passenger === null || $passenger === $passengers[0] ? 0.2 : -0.6, 1.02, 0);
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source instanceof EntityDamageByEntityEvent && ($damager = $source->getDamager()) instanceof Player && !$damager->hasFiniteResources()){
			$source->setBaseDamage($this->getMaxHealth());
		}else{
			//same as java, a boat soaks up a few hits before it breaks and recovers if you stop hitting it
			$source->setBaseDamage($source->getBaseDamage() * 10);
		}

		parent::attack($source);
		if($source->isCancelled()){
			return;
		}

		$this->hurtTime = self::HURT_ANIMATION_TICKS;
		$this->hurtDirection = -$this->hurtDirection;
		$this->networkPropertiesDirty = true;
	}

	protected function onDeath() : void{
		parent::onDeath();

		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			$killer = $this->lastDamageCause->getDamager();
			if($killer instanceof Player && !$killer->hasFiniteResources()){
				return;
			}
		}

		$this->getWorld()->dropItem($this->location, $this->getDropItem());
	}

	private function getDropItem() : Item{
		return match($this->boatType){
			BoatType::OAK => VanillaItems::OAK_BOAT(),
			BoatType::SPRUCE => VanillaItems::SPRUCE_BOAT(),
			BoatType::BIRCH => VanillaItems::BIRCH_BOAT(),
			BoatType::JUNGLE => VanillaItems::JUNGLE_BOAT(),
			BoatType::ACACIA => VanillaItems::ACACIA_BOAT(),
			BoatType::DARK_OAK => VanillaItems::DARK_OAK_BOAT(),
			BoatType::MANGROVE => VanillaItems::MANGROVE_BOAT(),
			BoatType::CHERRY => VanillaItems::CHERRY_BOAT(),
			BoatType::PALE_OAK => VanillaItems::PALE_OAK_BOAT(),
			BoatType::BAMBOO => VanillaItems::BAMBOO_RAFT(),
			BoatType::POPLAR => VanillaItems::POPLAR_BOAT(),
		};
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive() && $this->getHealth() < $this->getMaxHealth()){
			$this->setHealth(min($this->getMaxHealth(), $this->getHealth() + $tickDiff));
			$hasUpdate = true;
		}

		if($this->hurtTime > 0){
			$this->hurtTime = max(0, $this->hurtTime - $tickDiff);
			$this->networkPropertiesDirty = true;
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function hasMovementUpdate() : bool{
		//while someone is driving, the boat goes wherever their client says
		return $this->getControllingPassenger() === null && parent::hasMovementUpdate();
	}

	protected function tryChangeMovement() : void{
		$surface = $this->getWaterSurfaceHeight();
		if($surface === null){
			parent::tryChangeMovement();
			return;
		}

		//a damped spring towards the floating height, which also gives it a bit of a bob when it lands in water
		$sinkDepth = $surface - self::FLOAT_DEPTH - $this->location->y;
		$motionY = ($this->motion->y + $sinkDepth * 0.1) * 0.75;

		$this->motion = new Vector3($this->motion->x * 0.9, $motionY, $this->motion->z * 0.9);
	}

	/**
	 * Returns the height of the highest water surface the boat is touching, or null if it isn't in water.
	 */
	private function getWaterSurfaceHeight() : ?float{
		$world = $this->getWorld();
		$bb = $this->boundingBox;
		$surface = null;

		for($x = (int) floor($bb->minX), $maxX = (int) floor($bb->maxX); $x <= $maxX; ++$x){
			for($z = (int) floor($bb->minZ), $maxZ = (int) floor($bb->maxZ); $z <= $maxZ; ++$z){
				for($y = (int) floor($bb->minY), $maxY = (int) floor($bb->maxY); $y <= $maxY; ++$y){
					$block = $world->getBlockAt($x, $y, $z);
					if($block instanceof Water){
						//same surface maths as Entity::isUnderwater()
						$height = $y + 1 - ($block->getFluidHeightPercent() - 0.1111111);
						$surface = $surface === null ? $height : max($surface, $height);
					}
				}
			}
		}

		return $surface;
	}

	/**
	 * Takes the position of the boat from the client of the player driving it. Anything from a passenger who
	 * isn't steering is ignored, and a position that is too far away or in unloaded terrain gets the driver
	 * snapped back instead, for the same reasons as Player::handleMovement().
	 *
	 * @param Vector3 $networkPos where the client has the boat, raised by the network offset
	 */
	public function handleRiderInput(Player $rider, Vector3 $networkPos, float $yaw, bool $paddleLeft, bool $paddleRight) : void{
		if(!$this->isAlive() || $this->getControllingPassenger() !== $rider){
			return;
		}

		$newPos = $networkPos->subtract(0, self::NETWORK_OFFSET, 0);
		if($newPos->distanceSquared($this->location) > 225 || !$this->getWorld()->isInLoadedTerrain($newPos)){
			$rider->getNetworkSession()->sendDataPacket(MoveActorAbsolutePacket::create(
				$this->getId(),
				$this->getOffsetPosition($this->location),
				$this->location->pitch,
				$this->location->yaw,
				$this->location->yaw,
				MoveActorAbsolutePacket::FLAG_TELEPORT
			));
			return;
		}

		$yaw = fmod($yaw, 360);
		if($yaw < 0){
			$yaw += 360;
		}
		$this->setPositionAndRotation($newPos, $yaw, 0);
		$this->motion = Vector3::zero();
		$this->resetFallDistance();

		$this->setPaddles(
			$paddleLeft ? $this->paddleTimeLeft + self::PADDLE_SPEED : 0.0,
			$paddleRight ? $this->paddleTimeRight + self::PADDLE_SPEED : 0.0
		);

		$this->scheduleUpdate();
	}

	private function setPaddles(float $left, float $right) : void{
		if($left !== $this->paddleTimeLeft || $right !== $this->paddleTimeRight){
			$this->paddleTimeLeft = $left;
			$this->paddleTimeRight = $right;
			$this->networkPropertiesDirty = true;
		}
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3{
		return $vector3->add(0, self::NETWORK_OFFSET, 0);
	}

	/**
	 * @return Player[]
	 */
	private function getMovementViewers() : array{
		$viewers = $this->hasSpawned;
		$driver = $this->getControllingPassenger();
		if($driver !== null){
			//echoing the driver's own movement back makes the boat stutter on their screen
			unset($viewers[spl_object_id($driver)]);
		}
		return $viewers;
	}

	protected function broadcastMovement(bool $teleport = false) : void{
		NetworkBroadcastUtils::broadcastPackets($this->getMovementViewers(), [MoveActorAbsolutePacket::create(
			$this->id,
			$this->getOffsetPosition($this->location),
			$this->location->pitch,
			$this->location->yaw,
			$this->location->yaw,
			$this->onGround ? MoveActorAbsolutePacket::FLAG_GROUND : 0
		)]);
	}

	protected function broadcastMotion() : void{
		NetworkBroadcastUtils::broadcastPackets($this->getMovementViewers(), [SetActorMotionPacket::create($this->id, $this->getMotion(), tick: 0)]);
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setInt(EntityMetadataProperties::VARIANT, BoatTypeIdMap::getInstance()->toId($this->boatType));
		$properties->setInt(EntityMetadataProperties::HURT_TIME, $this->hurtTime);
		$properties->setInt(EntityMetadataProperties::HURT_DIRECTION, $this->hurtDirection);
		$properties->setFloat(EntityMetadataProperties::PADDLE_TIME_LEFT, $this->paddleTimeLeft);
		$properties->setFloat(EntityMetadataProperties::PADDLE_TIME_RIGHT, $this->paddleTimeRight);
		$properties->setByte(EntityMetadataProperties::CONTROLLING_RIDER_SEAT_NUMBER, 0);
		$properties->setByte(EntityMetadataProperties::IS_BUOYANT, 1);
		$properties->setString(EntityMetadataProperties::BUOYANCY_DATA, self::BUOYANCY_DATA);

		$properties->setGenericFlag(EntityMetadataFlags::STACKABLE, true);
		$properties->setGenericFlag(EntityMetadataFlags::COLLIDABLE, true);
	}
}
