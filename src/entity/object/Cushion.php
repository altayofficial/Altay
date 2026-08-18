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

use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class Cushion extends Living{
	private const TAG_COLOR = "Color"; //TAG_Byte

	/**
	 * The size and the model index below are what a server sends for a cushion it summons itself; a
	 * cushion is a flat pad a quarter of a block tall.
	 */
	private const MODEL_VARIANT = 15;

	private DyeColor $color = DyeColor::WHITE;

	public static function getNetworkTypeId() : string{ return "minecraft:cushion"; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.249, 0.999); }

	protected function getInitialDragMultiplier() : float{ return 0.02; }

	protected function getInitialGravity() : float{ return 0.04; }

	public function getName() : string{
		return "Cushion";
	}

	public function getColor() : DyeColor{ return $this->color; }

	/** @return $this */
	public function setColor(DyeColor $color) : self{
		$this->color = $color;
		$this->networkPropertiesDirty = true;
		return $this;
	}

	protected function initEntity(CompoundTag $nbt) : void{
		$this->setMaxHealth(1);

		parent::initEntity($nbt);

		$colorId = $nbt->getByte(self::TAG_COLOR, DyeColorIdMap::getInstance()->toId(DyeColor::WHITE));
		//a broken colour shouldn't stop the world from loading, so it falls back instead of throwing
		$this->color = DyeColorIdMap::getInstance()->fromId($colorId) ?? DyeColor::WHITE;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_COLOR, DyeColorIdMap::getInstance()->toId($this->color));

		return $nbt;
	}

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return [VanillaItems::CUSHION()->setColor($this->color)];
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setInt(EntityMetadataProperties::VARIANT, self::MODEL_VARIANT);
		$properties->setByte(EntityMetadataProperties::COLOR, DyeColorIdMap::getInstance()->toId($this->color));
		$properties->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
	}
}
