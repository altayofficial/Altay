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
use pocketmine\block\Liquid;
use pocketmine\block\Water;
use pocketmine\entity\Location;
use pocketmine\entity\object\Boat as BoatEntity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function count;
use function fmod;

class Boat extends Item{
	private BoatType $boatType;

	public function __construct(ItemIdentifier $identifier, string $name, BoatType $boatType){
		parent::__construct($identifier, $name);
		$this->boatType = $boatType;
	}

	public function getType() : BoatType{
		return $this->boatType;
	}

	public function getFuelTime() : int{
		return 1200; //400 in PC
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if($blockClicked instanceof Water){
			//dropped on top of the water, the boat sinks in to its floating height by itself
			$pos = $blockClicked->getPosition()->add(0.5, 1, 0.5);
		}elseif($face === Facing::UP && $blockReplace->canBeReplaced() && !$blockReplace instanceof Liquid){
			$pos = $blockReplace->getPosition()->add(0.5, 0, 0.5);
		}else{
			return ItemUseResult::NONE;
		}

		$world = $player->getWorld();
		$halfWidth = BoatEntity::WIDTH / 2;
		$boundingBox = new AxisAlignedBB(
			$pos->x - $halfWidth, $pos->y, $pos->z - $halfWidth,
			$pos->x + $halfWidth, $pos->y + BoatEntity::HEIGHT, $pos->z + $halfWidth
		);
		if(count($world->getCollisionBlocks($boundingBox)) > 0){
			return ItemUseResult::NONE;
		}

		//the boat model points sideways compared to the rider, so it gets turned a quarter to face where the player looks
		$yaw = fmod($player->getLocation()->getYaw() + 90, 360);
		$boat = new BoatEntity(Location::fromObject($pos, $world, $yaw, 0.0), $this->boatType);
		$boat->spawnToAll();

		$this->pop();
		return ItemUseResult::SUCCESS;
	}
}
