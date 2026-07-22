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
use pocketmine\block\BlockToolType;
use pocketmine\entity\Entity;

class Sword extends TieredTool{

	public function getBlockToolType() : int{
		return BlockToolType::SWORD;
	}

	public function getAttackPoints() : int{
		return $this->tier->getBaseAttackPoints();
	}

	public function getBlockToolHarvestLevel() : int{
		return 1;
	}

	public function getMiningEfficiency(bool $isCorrectTool) : float{
		return parent::getMiningEfficiency($isCorrectTool) * 1.5; //swords break any block 1.5x faster than hand
	}

	protected function getBaseMiningEfficiency() : float{
		return 10;
	}

	public function onDestroyBlock(Block $block, array &$returnedItems) : bool{
		if(!$block->getBreakInfo()->breaksInstantly()){
			return $this->applyDamage(2);
		}
		return false;
	}

	public function onAttackEntity(Entity $victim, array &$returnedItems) : bool{
		return $this->applyDamage(1);
	}
}
