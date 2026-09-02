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
use pocketmine\entity\Location;
use pocketmine\entity\object\ArmorStand as EntityArmorStand;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ArmorStandPlaceSound;
use function floor;
use function fmod;

class ArmorStand extends Item{

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		$above = $blockReplace->getSide(Facing::UP);
		if(!$blockReplace->canBeReplaced() || !$above->canBeReplaced()){
			return ItemUseResult::NONE;
		}

		$pos = $blockReplace->getPosition();
		$world = $pos->getWorld();

		$boundingBox = AxisAlignedBB::one()
			->offset($pos->getX(), $pos->getY(), $pos->getZ())
			->extend(Facing::UP, 1);
		foreach($world->getNearbyEntities($boundingBox) as $entity){
			if($entity instanceof EntityArmorStand){
				return ItemUseResult::NONE;
			}
		}

		$location = Location::fromObject(
			$pos->add(0.5, 0, 0.5),
			$world,
			$this->snapYaw($player->getLocation()->getYaw() + 180.0),
			0.0
		);

		$armorStand = new EntityArmorStand($location);
		$armorStand->spawnToAll();
		$world->addSound($location, new ArmorStandPlaceSound());

		$this->pop();
		return ItemUseResult::SUCCESS;
	}

	private function snapYaw(float $yaw) : float{
		$normalized = fmod(fmod($yaw, 360.0) + 360.0, 360.0); // meh, I'm not proud of this one
		return fmod(floor(($normalized + 22.5) / 45.0) * 45.0, 360.0);
	}

	public function getMaxStackSize() : int{
		return 16;
	}
}
