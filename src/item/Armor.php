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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use function mt_rand;

class Armor extends Durable implements DyeableItem{
	use CustomColorHandlingTrait;

	public const TAG_CUSTOM_COLOR = DyeableItem::TAG_CUSTOM_COLOR; // TODO: remove this, this is here for BC compatibility

	public const TAG_TRIM = "Trim"; //TAG_Compound
	public const TAG_TRIM_PATTERN = "Pattern"; //TAG_String
	public const TAG_TRIM_MATERIAL = "Material"; //TAG_String

	private ArmorTypeInfo $armorInfo;
	private ?ArmorTrim $trim = null;

	/**
	 * @param string[] $enchantmentTags
	 */
	public function __construct(ItemIdentifier $identifier, string $name, ArmorTypeInfo $info, array $enchantmentTags = []){
		parent::__construct($identifier, $name, $enchantmentTags);
		$this->armorInfo = $info;
	}

	public function getMaxDurability() : int{
		return $this->armorInfo->getMaxDurability();
	}

	public function getDefensePoints() : int{
		return $this->armorInfo->getDefensePoints();
	}

	/**
	 * @see ArmorInventory
	 */
	public function getArmorSlot() : int{
		return $this->armorInfo->getArmorSlot();
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function isFireProof() : bool{
		return $this->armorInfo->isFireProof();
	}

	public function getMaterial() : ArmorMaterial{
		return $this->armorInfo->getMaterial();
	}

	public function getTrim() : ?ArmorTrim{
		return $this->trim;
	}

	/** @return $this */
	public function setTrim(?ArmorTrim $trim) : self{
		$this->trim = $trim;
		return $this;
	}

	public function getEnchantability() : int{
		return $this->armorInfo->getMaterial()->getEnchantability();
	}

	/**
	 * Returns the total enchantment protection factor this armour piece offers from all applicable protection
	 * enchantments on the item.
	 */
	public function getEnchantmentProtectionFactor(EntityDamageEvent $event) : int{
		$epf = 0;

		foreach($this->getEnchantments() as $enchantment){
			$type = $enchantment->getType();
			if($type instanceof ProtectionEnchantment && $type->isApplicable($event)){
				$epf += $type->getProtectionFactor($enchantment->getLevel());
			}
		}

		return $epf;
	}

	protected function getUnbreakingDamageReduction(int $amount) : int{
		if(($unbreakingLevel = $this->getEnchantmentLevel(VanillaEnchantments::UNBREAKING())) > 0){
			$negated = 0;

			$chance = 1 / ($unbreakingLevel + 1);
			for($i = 0; $i < $amount; ++$i){
				if(mt_rand(1, 100) > 60 && Utils::getRandomFloat() > $chance){ //unbreaking only applies to armor 40% of the time at best
					$negated++;
				}
			}

			return $negated;
		}

		return 0;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		$existing = $player->getArmorInventory()->getItem($this->getArmorSlot());
		$thisCopy = clone $this;
		$new = $thisCopy->pop();
		$player->getArmorInventory()->setItem($this->getArmorSlot(), $new);
		$player->getInventory()->setItemInHand($existing);
		$sound = $new->getMaterial()->getEquipSound();
		if($sound !== null){
			$player->broadcastSound($sound);
		}
		if(!$thisCopy->isNull()){
			//if the stack size was bigger than 1 (usually won't happen, but might be caused by plugins)
			$returnedItems[] = $thisCopy;
		}
		return ItemUseResult::SUCCESS;
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);
		$this->deserializeCustomColor($tag);

		$trimTag = $tag->getCompoundTag(self::TAG_TRIM);
		$this->trim = $trimTag !== null ?
			new ArmorTrim($trimTag->getString(self::TAG_TRIM_PATTERN, ""), $trimTag->getString(self::TAG_TRIM_MATERIAL, "")) :
			null;
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);
		$this->serializeCustomColor($tag);

		if($this->trim !== null){
			$tag->setTag(self::TAG_TRIM, CompoundTag::create()
				->setString(self::TAG_TRIM_PATTERN, $this->trim->getPatternId())
				->setString(self::TAG_TRIM_MATERIAL, $this->trim->getMaterialId()));
		}else{
			$tag->removeTag(self::TAG_TRIM);
		}
	}
}
