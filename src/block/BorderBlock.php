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

use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class BorderBlock extends Wall{

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null && !$this->canBeModifiedBy($player)){
			return false;
		}

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null && !$this->canBeModifiedBy($player)){
			//creative players destroy blocks clientside without waiting for the server, so the block has to be resent
			$this->position->getWorld()->setBlock($this->position, $this);
			return false;
		}

		return parent::onBreak($item, $player, $returnedItems);
	}

	public function getDrops(Item $item) : array{
		return [];
	}

	protected function recalculateCollisionBoxes() : array{
		//the vanilla border block is infinitely tall, but the collision box cache only accounts for overflow into
		//directly adjacent blocks, so this is the tallest box the engine can actually collide with
		$boxes = parent::recalculateCollisionBoxes();
		foreach($boxes as $box){
			$box->extend(Facing::UP, 0.5);
		}

		return $boxes;
	}

	private function canBeModifiedBy(Player $player) : bool{
		return $player->isCreative() && $player->hasPermission(DefaultPermissions::ROOT_OPERATOR);
	}
}
