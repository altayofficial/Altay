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

use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingOption;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\StairShape;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class Stair extends Transparent implements HorizontalFacing, StateDeriving{
	use HorizontalFacingTrait;

	protected bool $upsideDown = false;
	protected StairShape $shape = StairShape::STRAIGHT;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->facing);
		$w->bool($this->upsideDown);
		$w->enum($this->shape);
	}

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();

		$this->collisionBoxes = null;

		return $this;
	}

	public function onNearbyBlockChange() : void{
		if($this->deriveStateFromWorld()){
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function deriveStateFromWorld() : bool{
		$clockwise = Facing::rotateY($this->facing, true);
		if(
			($backFacing = $this->getPossibleCornerFacing(false)) !== null &&
			$this->canTakeShape(Facing::opposite($backFacing))
		){
			$shape = $backFacing === $clockwise ? StairShape::OUTER_RIGHT : StairShape::OUTER_LEFT;
		}elseif(
			($frontFacing = $this->getPossibleCornerFacing(true)) !== null &&
			$this->canTakeShape($frontFacing)
		){
			$shape = $frontFacing === $clockwise ? StairShape::INNER_RIGHT : StairShape::INNER_LEFT;
		}else{
			$shape = StairShape::STRAIGHT;
		}

		if($shape === $this->shape){
			return false;
		}

		$this->shape = $shape;
		$this->collisionBoxes = null;
		return true;
	}

	public function isUpsideDown() : bool{ return $this->upsideDown; }

	/** @return $this */
	public function setUpsideDown(bool $upsideDown) : self{
		$this->upsideDown = $upsideDown;
		return $this;
	}

	public function getShape() : StairShape{ return $this->shape; }

	/** @return $this */
	public function setShape(StairShape $shape) : self{
		$this->shape = $shape;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$topStepFace = $this->upsideDown ? Facing::DOWN : Facing::UP;
		$bbs = [
			AxisAlignedBB::one()->trimmedCopy($topStepFace, 0.5)
		];

		$realFacing = $this->facing->toFacing();
		$topStep = AxisAlignedBB::one()
			->trimmedCopy(Facing::opposite($topStepFace), 0.5)
			->trimmedCopy(Facing::opposite($realFacing), 0.5);

		if($this->shape === StairShape::OUTER_LEFT || $this->shape === StairShape::OUTER_RIGHT){
			$topStep = $topStep->trimmedCopy(Facing::rotateY($realFacing, $this->shape === StairShape::OUTER_LEFT), 0.5);
		}elseif($this->shape === StairShape::INNER_LEFT || $this->shape === StairShape::INNER_RIGHT){
			//add an extra cube
			$bbs[] = AxisAlignedBB::one()
				->trimmedCopy(Facing::opposite($topStepFace), 0.5)
				->trimmedCopy($realFacing, 0.5) //avoid overlapping with main step
				->trimmedCopy(Facing::rotateY($realFacing, $this->shape === StairShape::INNER_LEFT), 0.5);
		}

		$bbs[] = $topStep;

		return $bbs;
	}

	public function getSupportType(Facing $facing) : SupportType{
		$realFacing = $this->facing->toFacing();
		if(
			$facing === Facing::UP && $this->upsideDown ||
			$facing === Facing::DOWN && !$this->upsideDown ||
			($facing === $realFacing && $this->shape !== StairShape::OUTER_LEFT && $this->shape !== StairShape::OUTER_RIGHT) ||
			($facing === Facing::rotate($realFacing, Axis::Y, false) && $this->shape === StairShape::INNER_LEFT) ||
			($facing === Facing::rotate($realFacing, Axis::Y, true) && $this->shape === StairShape::INNER_RIGHT)
		){
			return SupportType::FULL;
		}
		return SupportType::NONE;
	}

	private function getPossibleCornerFacing(bool $oppositeFacing) : ?HorizontalFacingOption{
		$realFacing = $this->facing->toFacing();
		$side = $this->getSide($oppositeFacing ? Facing::opposite($realFacing) : $realFacing);
		return (
			$side instanceof Stair &&
			$side->upsideDown === $this->upsideDown &&
			Facing::axis($side->facing->toFacing()) !== Facing::axis($realFacing) //perpendicular
		) ? $side->facing : null;
	}

	private function canTakeShape(Facing $facing) : bool{
		$side = $this->getSide($facing);
		return !($side instanceof Stair) || $side->facing !== $this->facing || $side->upsideDown !== $this->upsideDown;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, Facing $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null){
			$this->facing = HorizontalFacingOption::fromFacing($player->getHorizontalFacing());
		}
		$this->upsideDown = (($clickVector->y > 0.5 && $face !== Facing::UP) || $face === Facing::DOWN);

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}
}
