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

namespace pocketmine\block;

use pocketmine\block\tile\CopperGolemStatue as TileCopperGolemStatue;
use pocketmine\block\utils\CopperGolemStatuePose;
use pocketmine\block\utils\CopperMaterial;
use pocketmine\block\utils\CopperTrait;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\SupportType;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function assert;

class CopperGolemStatue extends Transparent implements CopperMaterial, HorizontalFacing{
	use CopperTrait{
		onInteract as onInteractCopper;
	}
	use FacesOppositePlacingPlayerTrait;

	protected CopperGolemStatuePose $pose = CopperGolemStatuePose::STANDING;

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCopperGolemStatue){
			$this->pose = $tile->getPose();
		}

		return $this;
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();
		//pose isn't a blockstate, the client reads it from the tile
		$tile = $this->position->getWorld()->getTile($this->position);
		assert($tile instanceof TileCopperGolemStatue);
		$tile->setPose($this->pose);
	}

	public function getPose() : CopperGolemStatuePose{ return $this->pose; }

	/** @return $this */
	public function setPose(CopperGolemStatuePose $pose) : self{
		$this->pose = $pose;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->contract(3 / 16, 0, 3 / 16)->trim(Facing::UP, 2 / 16)];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->onInteractCopper($item, $face, $clickVector, $player, $returnedItems)){
			return true;
		}

		if($item->isNull()){
			$this->pose = $this->pose->getNext();
			$this->position->getWorld()->setBlock($this->position, $this);
			return true;
		}

		return false;
	}
}
