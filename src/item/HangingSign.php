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
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

final class HangingSign extends Item{

	public function __construct(
		ItemIdentifier $identifier,
		string $name,
		private Block $centerPointCeilingVariant,
		private Block $edgePointCeilingVariant,
		private Block $wallVariant
	){
		parent::__construct($identifier, $name);
	}

	public function getPlacementTransaction(Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : ?BlockTransaction{
		if($face !== Facing::DOWN){
			return $this->tryPlacementTransaction(clone $this->wallVariant, $blockReplace, $blockClicked, $face, $clickVector, $player);
		}
		//ceiling edges sign has stricter placement conditions than ceiling center sign, so try that first
		$ceilingEdgeTx = $player === null || !$player->isSneaking() ?
			$this->tryPlacementTransaction(clone $this->edgePointCeilingVariant, $blockReplace, $blockClicked, $face, $clickVector, $player) :
			null;
		return $ceilingEdgeTx ?? $this->tryPlacementTransaction(clone $this->centerPointCeilingVariant, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function getBlock(?int $clickedFace = null) : Block{
		//we don't have enough information here to decide which ceiling type to use
		return $clickedFace === Facing::DOWN ? clone $this->centerPointCeilingVariant : clone $this->wallVariant;
	}

	public function getMaxStackSize() : int{
		return 16;
	}

	public function getFuelTime() : int{
		return 200;
	}
}
