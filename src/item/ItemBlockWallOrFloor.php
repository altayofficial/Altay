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
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Axis;
use pocketmine\math\Facing;

class ItemBlockWallOrFloor extends Item{
	private int $floorVariant;
	private int $wallVariant;

	public function __construct(ItemIdentifier $identifier, Block $floorVariant, Block $wallVariant){
		parent::__construct($identifier, $floorVariant->getName());
		$this->floorVariant = $floorVariant->getStateId();
		$this->wallVariant = $wallVariant->getStateId();
	}

	public function getBlock(?int $clickedFace = null) : Block{
		if($clickedFace !== null && Facing::axis($clickedFace) !== Axis::Y){
			return RuntimeBlockStateRegistry::getInstance()->fromStateId($this->wallVariant);
		}
		return RuntimeBlockStateRegistry::getInstance()->fromStateId($this->floorVariant);
	}

	public function getFuelTime() : int{
		return $this->getBlock()->getFuelTime();
	}

	public function getMaxStackSize() : int{
		return $this->getBlock()->getMaxStackSize();
	}
}
