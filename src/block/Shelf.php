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

use pocketmine\block\tile\Shelf as TileShelf;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\PoweredShelfType;
use pocketmine\block\utils\WoodMaterial;
use pocketmine\block\utils\WoodTypeTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_reverse;
use function floor;
use function min;

class Shelf extends Transparent implements HorizontalFacing, WoodMaterial{
	use WoodTypeTrait;
	use FacesOppositePlacingPlayerTrait;

	public const SLOT_COUNT = 3;

	protected bool $powered = false;
	protected PoweredShelfType $poweredShelfType = PoweredShelfType::UNCONNECTED;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->powered);
		$w->enum($this->poweredShelfType);
	}

	public function isPowered() : bool{
		return $this->powered;
	}

	/** @return $this */
	public function setPowered(bool $powered) : self{
		$this->powered = $powered;
		return $this;
	}

	public function getPoweredShelfType() : PoweredShelfType{
		return $this->poweredShelfType;
	}

	/** @return $this */
	public function setPoweredShelfType(PoweredShelfType $poweredShelfType) : self{
		$this->poweredShelfType = $poweredShelfType;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		return [
			AxisAlignedBB::one()->trim($this->facing, 13 / 16),
			AxisAlignedBB::one()->trim($this->facing, 11 / 16)->trim(Facing::UP, 12 / 16),
			AxisAlignedBB::one()->trim($this->facing, 11 / 16)->trim(Facing::DOWN, 12 / 16)
		];
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($face !== $this->facing || $clickVector->y <= 0.25 || $clickVector->y >= 0.75){
			return false;
		}

		if($this->powered && $player !== null){
			$this->swapWithHotbar($player);
			return true;
		}

		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileShelf){
			return false;
		}
		$inventory = $tile->getInventory();
		$slot = $this->getSlotFromClick($face, $clickVector);

		$stored = $inventory->getItem($slot);
		if($item->isNull() && $stored->isNull()){
			return true;
		}
		$inventory->setItem($slot, $item->isNull() ? VanillaItems::AIR() : $item->pop($item->getCount()));
		//hmm, creative players keep their held item and don't get the old one back, same as vanilla right???
		if(!$stored->isNull() && ($player === null || $player->hasFiniteResources())){
			$returnedItems[] = $stored;
		}
		return true;
	}

	/**
	 * Slot 0 is the one on the left as seen by whoever is looking at the front.
	 */
	private function getSlotFromClick(int $face, Vector3 $clickVector) : int{
		$x = Facing::axis($face) === Axis::X ? $clickVector->z : $clickVector->x;
		$offset = Facing::isPositive(Facing::rotateY($face, true)) ? 1 - $x : $x;
		return min(self::SLOT_COUNT - 1, (int) floor($offset * self::SLOT_COUNT));
	}

	/**
	 * Swaps every item on this shelf and the ones linked to it with the player's hotbar, three slots per shelf.
	 */
	private function swapWithHotbar(Player $player) : void{
		$playerInventory = $player->getInventory();
		$world = $this->position->getWorld();
		foreach($this->getConnectedShelves() as $index => $shelf){
			$tile = $world->getTile($shelf->position);
			if(!$tile instanceof TileShelf){
				continue;
			}
			$shelfInventory = $tile->getInventory();
			for($slot = 0; $slot < self::SLOT_COUNT; $slot++){
				$hotbarSlot = $index * self::SLOT_COUNT + $slot;
				$fromShelf = $shelfInventory->getItem($slot);
				$shelfInventory->setItem($slot, $playerInventory->getItem($hotbarSlot));
				$playerInventory->setItem($hotbarSlot, $fromShelf);
			}
		}
	}

	/**
	 * Returns this shelf and the powered shelves linked to it, ordered left to right as the player sees them.
	 *
	 * @return Shelf[]
	 * @phpstan-return list<Shelf>
	 */
	public function getConnectedShelves() : array{
		if(!$this->powered || $this->poweredShelfType === PoweredShelfType::UNCONNECTED){
			return [$this];
		}

		//yeah, "right" is the shelf's own right, so it's on the viewer's left
		$rightFace = Facing::rotateY($this->facing, true);
		$leftFace = Facing::opposite($rightFace);
		$right = $this->getSide($rightFace);
		$left = $this->getSide($leftFace);

		$shelves = [];
		switch($this->poweredShelfType){
			case PoweredShelfType::CENTER:
				if($right instanceof Shelf){
					$shelves[] = $right;
				}
				$shelves[] = $this;
				if($left instanceof Shelf){
					$shelves[] = $left;
				}
				break;
			case PoweredShelfType::RIGHT:
				$shelves[] = $this;
				if($right instanceof Shelf){
					$shelves[] = $right;
					if($right->poweredShelfType === PoweredShelfType::CENTER && ($farRight = $right->getSide($rightFace)) instanceof Shelf){
						$shelves[] = $farRight;
					}
				}
				$shelves = array_reverse($shelves);
				break;
			case PoweredShelfType::LEFT:
				$shelves[] = $this;
				if($left instanceof Shelf){
					$shelves[] = $left;
					if($left->poweredShelfType === PoweredShelfType::CENTER && ($farLeft = $left->getSide($leftFace)) instanceof Shelf){
						$shelves[] = $farLeft;
					}
				}
				break;
		}
		return $shelves;
	}

	public function getFuelTime() : int{
		return $this->woodType->isFlammable() ? 300 : 0;
	}

	public function getFlameEncouragement() : int{
		return $this->woodType->isFlammable() ? 30 : 0;
	}

	public function getFlammability() : int{
		return $this->woodType->isFlammable() ? 20 : 0;
	}
}
