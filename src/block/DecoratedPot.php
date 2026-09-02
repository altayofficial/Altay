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

use pocketmine\block\tile\DecoratedPot as TileDecoratedPot;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\PotDecorations;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\sound\PotShatterSound;

class DecoratedPot extends Transparent implements HorizontalFacing{
	use FacesOppositePlacingPlayerTrait;

	private ?PotDecorations $decorations = null;

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();

		$tile = $this->position->getWorld()->getTile($this->position);
		$this->decorations = $tile instanceof TileDecoratedPot ? $tile->getDecorations() : null;

		return $this;
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();

		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileDecoratedPot){
			$tile->setDecorations($this->getDecorations());
		}
	}

	public function getDecorations() : PotDecorations{
		return $this->decorations ??= new PotDecorations();
	}

	/** @return $this */
	public function setDecorations(PotDecorations $decorations) : self{
		$this->decorations = $decorations;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->contract(1 / 16, 0, 1 / 16)];
	}

	public function isAffectedBySilkTouch() : bool{
		return true;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return $this->getShatterDrops();
	}

	public function getDropsForIncompatibleTool(Item $item) : array{
		return $this->getShatterDrops();
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []) : bool{
		$shattered = !$this->isSilkTouchBreak($item);

		if(!parent::onBreak($item, $player, $returnedItems)){
			return false;
		}

		if($shattered){
			$this->position->getWorld()->addSound($this->position->add(0.5, 0.5, 0.5), new PotShatterSound());
		}

		return true;
	}

	private function isSilkTouchBreak(Item $item) : bool{
		return $this->getBreakInfo()->isToolCompatible($item) && $item->hasEnchantment(VanillaEnchantments::SILK_TOUCH());
	}

	/**
	 * Returns the sherds the pot breaks apart into. Faces without a sherd yield the plain brick they were built from.
	 *
	 * @return Item[]
	 * @phpstan-return list<Item>
	 */
	private function getShatterDrops() : array{
		$drops = [];
		foreach($this->getDecorations()->toArray() as $face){
			$drops[] = $face === null ? VanillaItems::BRICK() : VanillaItems::POTTERY_SHERD()->setSherdType($face);
		}

		return $drops;
	}
}
