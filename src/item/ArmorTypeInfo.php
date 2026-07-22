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

class ArmorTypeInfo{
	private ArmorMaterial $material;

	public function __construct(
		private int $defensePoints,
		private int $maxDurability,
		private int $armorSlot,
		private int $toughness = 0,
		private bool $fireProof = false,
		?ArmorMaterial $material = null
	){
		$this->material = $material ?? VanillaArmorMaterials::LEATHER();
	}

	public function getDefensePoints() : int{
		return $this->defensePoints;
	}

	public function getMaxDurability() : int{
		return $this->maxDurability;
	}

	public function getArmorSlot() : int{
		return $this->armorSlot;
	}

	public function getToughness() : int{
		return $this->toughness;
	}

	public function isFireProof() : bool{
		return $this->fireProof;
	}

	public function getMaterial() : ArmorMaterial{
		return $this->material;
	}
}
