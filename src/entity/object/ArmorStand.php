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

use pocketmine\block\BlockTypeIds;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\Shield;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\player\Player;
use pocketmine\world\sound\ArmorStandBreakSound;
use pocketmine\world\sound\ArmorStandFallSound;
use pocketmine\world\sound\ArmorStandHitSound;
use pocketmine\world\sound\ArmorStandPlaceSound;
use pocketmine\world\sound\Sound;
use function count;
use function max;
use function min;

class ArmorStand extends Living{
	public const SLOT_MAIN_HAND = 0;
	public const SLOT_OFF_HAND = 1;

	public const MAX_POSE_INDEX = 12;

	private const HURT_TIME_TICKS = 9;

	private const TAG_MAIN_HAND = "Mainhand"; //TAG_Compound
	private const TAG_OFF_HAND = "Offhand"; //TAG_Compound
	private const TAG_ARMOR = "Armor"; //TAG_List<TAG_Compound>
	private const TAG_POSE_INDEX = "PoseIndex"; //TAG_Int
	private const TAG_ARMOR_SLOT = "Slot"; //TAG_Byte

	public static function getNetworkTypeId() : string{ return EntityIds::ARMOR_STAND; }

	protected SimpleInventory $handInventory;

	protected int $poseIndex = 0;

	protected int $hurtTime = 0;

	protected int $maxDeadTicks = 1;

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(1.975, 0.5); }

	protected function getInitialDragMultiplier() : float{ return 0.02; }

	protected function getInitialGravity() : float{ return 0.04; }

	public function getName() : string{
		return "Armor Stand";
	}

	protected function initEntity(CompoundTag $nbt) : void{
		$this->setMaxHealth(6);

		parent::initEntity($nbt);

		$this->handInventory = new SimpleInventory(2);
		$this->handInventory->getListeners()->add(CallbackInventoryListener::onAnyChange(
			fn() => $this->sendHandItems($this->getViewers())
		));

		$armorTag = $nbt->getListTag(self::TAG_ARMOR, CompoundTag::class);
		if($armorTag !== null){
			foreach($armorTag as $armorItemTag){
				$this->armorInventory->setItem(
					$armorItemTag->getByte(self::TAG_ARMOR_SLOT, ArmorInventory::SLOT_HEAD),
					Item::safeNbtDeserialize($armorItemTag, "Armor stand armor item")
				);
			}
		}

		$mainHandTag = $nbt->getCompoundTag(self::TAG_MAIN_HAND);
		if($mainHandTag !== null){
			$this->handInventory->setItem(self::SLOT_MAIN_HAND, Item::safeNbtDeserialize($mainHandTag, "Armor stand main hand item"));
		}

		$offHandTag = $nbt->getCompoundTag(self::TAG_OFF_HAND);
		if($offHandTag !== null){
			$this->handInventory->setItem(self::SLOT_OFF_HAND, Item::safeNbtDeserialize($offHandTag, "Armor stand off hand item"));
		}

		//the pose index is clamped instead of validated, since a broken value shouldn't prevent the world from loading
		$this->setPoseIndex(max(0, min(self::MAX_POSE_INDEX, $nbt->getInt(self::TAG_POSE_INDEX, 0))));
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();

		$armorTag = new ListTag();
		foreach($this->armorInventory->getContents() as $slot => $item){
			$armorTag->push($item->nbtSerialize($slot));
		}
		$nbt->setTag(self::TAG_ARMOR, $armorTag);

		$nbt->setTag(self::TAG_MAIN_HAND, $this->handInventory->getItem(self::SLOT_MAIN_HAND)->nbtSerialize());
		$nbt->setTag(self::TAG_OFF_HAND, $this->handInventory->getItem(self::SLOT_OFF_HAND)->nbtSerialize());
		$nbt->setInt(self::TAG_POSE_INDEX, $this->poseIndex);

		return $nbt;
	}

	public function getHandInventory() : SimpleInventory{
		return $this->handInventory;
	}

	public function getPoseIndex() : int{
		return $this->poseIndex;
	}

	public function setPoseIndex(int $poseIndex) : void{
		if($poseIndex < 0 || $poseIndex > self::MAX_POSE_INDEX){
			throw new \InvalidArgumentException("Pose index must be in range 0-" . self::MAX_POSE_INDEX);
		}
		$this->poseIndex = $poseIndex;
		$this->networkPropertiesDirty = true;
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::ARMOR_STAND();
	}

	public function canBeRenamed() : bool{
		return true;
	}

	public function canBreathe() : bool{
		return true;
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($player->isSpectator()){
			return false;
		}

		if($player->isSneaking()){
			$this->setPoseIndex($this->poseIndex >= self::MAX_POSE_INDEX ? 0 : $this->poseIndex + 1);
			return true;
		}

		$heldItem = $player->getInventory()->getItemInHand();
		$target = $this->findEquipmentSlot($heldItem, $clickPos->getY());
		if($target === null){
			return false;
		}
		[$inventory, $slot] = $target;

		$oldItem = $inventory->getItem($slot);
		if(!$heldItem->isNull() && $oldItem->getCount() > 1){
			//swapping isn't possible if the armor stand holds more than one item in the slot
			return false;
		}

		$newItem = $heldItem->isNull() ? VanillaItems::AIR() : (clone $heldItem)->setCount(1);
		$inventory->setItem($slot, $newItem);

		if($player->hasFiniteResources()){
			if(!$heldItem->isNull()){
				$heldItem->pop();
				$player->getInventory()->setItemInHand($heldItem);
			}
			if(!$oldItem->isNull()){
				foreach($player->getInventory()->addItem($oldItem) as $failed){
					$player->dropItem($failed);
				}
			}
		}

		$this->broadcastSound(new ArmorStandPlaceSound());
		return true;
	}

	/**
	 * @return array{Inventory, int}|null
	 */
	private function findEquipmentSlot(Item $item, float $clickHeight) : ?array{
		if($item instanceof Armor){
			return [$this->armorInventory, $item->getArmorSlot()];
		}
		if($item instanceof ItemBlock && (
			$item->getBlock()->getTypeId() === BlockTypeIds::CARVED_PUMPKIN ||
			$item->getBlock()->getTypeId() === BlockTypeIds::MOB_HEAD
		)){
			return [$this->armorInventory, ArmorInventory::SLOT_HEAD];
		}
		if(!$item->isNull()){
			return [$this->handInventory, $item instanceof Shield ? self::SLOT_OFF_HAND : self::SLOT_MAIN_HAND];
		}

		if($clickHeight >= 0.1 && $clickHeight < 0.55 && !$this->armorInventory->getBoots()->isNull()){
			return [$this->armorInventory, ArmorInventory::SLOT_FEET];
		}
		if($clickHeight >= 0.4 && $clickHeight < 1.2 && !$this->armorInventory->getLeggings()->isNull()){
			return [$this->armorInventory, ArmorInventory::SLOT_LEGS];
		}
		if($clickHeight >= 1.6 && !$this->armorInventory->getHelmet()->isNull()){
			return [$this->armorInventory, ArmorInventory::SLOT_HEAD];
		}
		if(!$this->handInventory->getItem(self::SLOT_OFF_HAND)->isNull()){
			return [$this->handInventory, self::SLOT_OFF_HAND];
		}
		if(!$this->handInventory->getItem(self::SLOT_MAIN_HAND)->isNull()){
			return [$this->handInventory, self::SLOT_MAIN_HAND];
		}
		if(!$this->armorInventory->getChestplate()->isNull()){
			return [$this->armorInventory, ArmorInventory::SLOT_CHEST];
		}

		return null;
	}

	public function attack(EntityDamageEvent $source) : void{
		$cause = $source->getCause();
		if(
			$cause === EntityDamageEvent::CAUSE_CONTACT ||
			$cause === EntityDamageEvent::CAUSE_DROWNING ||
			$cause === EntityDamageEvent::CAUSE_SUFFOCATION ||
			$cause === EntityDamageEvent::CAUSE_MAGIC ||
			$cause === EntityDamageEvent::CAUSE_STARVATION
		){
			$source->cancel();
		}elseif($cause === EntityDamageEvent::CAUSE_FALL){
			$source->cancel();
			$this->broadcastSound(new ArmorStandFallSound());
		}

		if(
			$source instanceof EntityDamageByEntityEvent &&
			($damager = $source->getDamager()) instanceof Player &&
			!$damager->hasFiniteResources()
		){
			//creative players destroy armor stands instantly, without dropping the stand itself
			$source->call();
			if(!$source->isCancelled()){
				$this->destroyInCreative();
			}
			return;
		}

		parent::attack($source);

		if(!$source->isCancelled() && $this->isAlive()){
			$this->setHurtTime(self::HURT_TIME_TICKS);
			$this->broadcastSound(new ArmorStandHitSound());
		}
	}

	public function getHurtTime() : int{
		return $this->hurtTime;
	}

	/**
	 * Sets how long the armor stand wobbles for after being hit, in ticks.
	 */
	public function setHurtTime(int $hurtTime) : void{
		if($hurtTime < 0){
			throw new \InvalidArgumentException("Hurt time cannot be negative");
		}
		$this->hurtTime = $hurtTime;
		$this->networkPropertiesDirty = true;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->hurtTime > 0 && $this->ticksLived % 2 === 0){
			$this->setHurtTime($this->hurtTime - 1);
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function knockBack(float $x, float $z, float $force = self::DEFAULT_KNOCKBACK_FORCE, ?float $verticalLimit = self::DEFAULT_KNOCKBACK_VERTICAL_LIMIT) : void{
		//armor stands don't get pushed around when they are hit
	}

	protected function doHitAnimation() : void{
		//armor stands don't flash red or play the mob hurt sound when damaged
	}

	public function getAttackSound() : ?Sound{
		return null; //the armor stand hit sound is played by attack() instead
	}

	private function destroyInCreative() : void{
		foreach($this->getEquipmentDrops() as $item){
			$this->getWorld()->dropItem($this->location, $item);
		}
		$this->armorInventory->clearAll();
		$this->handInventory->clearAll();

		$this->broadcastSound(new ArmorStandBreakSound());
		$this->flagForDespawn();
	}

	/**
	 * @return Item[]
	 */
	private function getEquipmentDrops() : array{
		$drops = [];
		foreach([...$this->armorInventory->getContents(), ...$this->handInventory->getContents()] as $item){
			if(!$item->isNull()){
				$drops[] = $item;
			}
		}
		return $drops;
	}

	public function getDrops() : array{
		return [...$this->getEquipmentDrops(), VanillaItems::ARMOR_STAND()];
	}

	protected function startDeathAnimation() : void{
		$this->broadcastSound(new ArmorStandBreakSound());
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setInt(EntityMetadataProperties::ARMOR_STAND_POSE_INDEX, $this->poseIndex);
		$properties->setInt(EntityMetadataProperties::HURT_TIME, $this->hurtTime);
		$properties->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$this->sendHandItems([$player]);
	}

	/**
	 * @param Player[] $targets
	 */
	private function sendHandItems(array $targets) : void{
		if(count($targets) === 0){
			return;
		}

		$typeConverter = TypeConverter::getInstance();
		NetworkBroadcastUtils::broadcastPackets($targets, [
			MobEquipmentPacket::create(
				$this->getId(),
				TypeConverter::legacyItemStackWrapper($typeConverter->coreItemStackToNet($this->handInventory->getItem(self::SLOT_MAIN_HAND))),
				self::SLOT_MAIN_HAND,
				self::SLOT_MAIN_HAND,
				ContainerIds::INVENTORY
			),
			MobEquipmentPacket::create(
				$this->getId(),
				TypeConverter::legacyItemStackWrapper($typeConverter->coreItemStackToNet($this->handInventory->getItem(self::SLOT_OFF_HAND))),
				0,
				0,
				ContainerIds::OFFHAND
			)
		]);
	}

	protected function onDispose() : void{
		$this->handInventory->removeAllViewers();
		parent::onDispose();
	}

	protected function destroyCycles() : void{
		unset($this->handInventory);
		parent::destroyCycles();
	}
}
