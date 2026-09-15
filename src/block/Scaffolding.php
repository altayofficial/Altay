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

use pocketmine\block\utils\Fallable;
use pocketmine\block\utils\FallableTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\Position;
use function min;

class Scaffolding extends Transparent implements Fallable{
	use FallableTrait {
		onNearbyBlockChange as private checkFall;
	}

	public const MIN_STABILITY = 0;

	/**
	 * Stability of a scaffolding that has no support at all. Such a block cannot stay in place.
	 */
	public const MAX_STABILITY = 7;

	protected int $stability = self::MIN_STABILITY;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(self::MIN_STABILITY, self::MAX_STABILITY, $this->stability);
	}

	public function getStability() : int{
		return $this->stability;
	}

	/** @return $this */
	public function setStability(int $stability) : self{
		if($stability < self::MIN_STABILITY || $stability > self::MAX_STABILITY){
			throw new \InvalidArgumentException("Stability must be in range " . self::MIN_STABILITY . " ... " . self::MAX_STABILITY);
		}
		$this->stability = $stability;
		return $this;
	}

	public function isSolid() : bool{
		return false;
	}

	public function canClimb() : bool{
		return true;
	}

	public function canBeFlowedInto() : bool{
		return false;
	}

	/**
	 * Only the top face is solid enough to walk on. The rest of the block is hollow, which is what makes it
	 * possible to climb through a scaffolding tower.
	 */
	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::DOWN, 14 / 16)];
	}

	public function getSupportType(int $facing) : SupportType{
		return $facing === Facing::UP ? SupportType::FULL : SupportType::NONE;
	}

	public function getFlameEncouragement() : int{
		return 60;
	}

	public function getFlammability() : int{
		return 20;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$stability = $this->recalculateStability($blockReplace->getPosition());
		if($stability >= self::MAX_STABILITY){
			return false;
		}

		$this->stability = $stability;
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onNearbyBlockChange() : void{
		$stability = $this->recalculateStability($this->position);
		if($stability >= self::MAX_STABILITY){
			$this->checkFall();
			return;
		}

		if($stability !== $this->stability){
			$this->position->getWorld()->setBlock($this->position, $this->setStability($stability));
		}
	}

	/**
	 * A scaffolding sitting on a solid block is fully stable. Otherwise it inherits the stability of the
	 * scaffolding below it, or the stability of the closest horizontal neighbour plus one, which is what limits
	 * how far a scaffolding bridge can reach away from its support.
	 */
	private function recalculateStability(Position $position) : int{
		$world = $position->getWorld();
		if($world->getBlock($position->getSide(Facing::DOWN))->isSolid()){
			return self::MIN_STABILITY;
		}

		$stability = self::MAX_STABILITY;
		foreach(Facing::ALL as $facing){
			if($facing === Facing::UP){
				continue;
			}

			$neighbor = $world->getBlock($position->getSide($facing));
			if(!$neighbor instanceof Scaffolding){
				continue;
			}

			$stability = min($stability, $facing === Facing::DOWN ? $neighbor->stability : $neighbor->stability + 1);
		}

		return $stability;
	}
}
