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

use pocketmine\color\Color;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\Binary;

/**
 * Handles the dyed colour stored in the NBT of items which can be dyed.
 */
trait CustomColorHandlingTrait{

	protected ?Color $customColor = null;

	public function getCustomColor() : ?Color{
		return $this->customColor;
	}

	/** @return $this */
	public function setCustomColor(Color $color) : self{
		$this->customColor = $color;
		return $this;
	}

	/** @return $this */
	public function clearCustomColor() : self{
		$this->customColor = null;
		return $this;
	}

	protected function deserializeCustomColor(CompoundTag $tag) : void{
		if(($colorTag = $tag->getTag(DyeableItem::TAG_CUSTOM_COLOR)) instanceof IntTag){
			$this->customColor = Color::fromARGB(Binary::unsignInt($colorTag->getValue()));
		}else{
			$this->customColor = null;
		}
	}

	protected function serializeCustomColor(CompoundTag $tag) : void{
		$this->customColor !== null ?
			$tag->setInt(DyeableItem::TAG_CUSTOM_COLOR, Binary::signInt($this->customColor->toARGB())) :
			$tag->removeTag(DyeableItem::TAG_CUSTOM_COLOR);
	}
}
