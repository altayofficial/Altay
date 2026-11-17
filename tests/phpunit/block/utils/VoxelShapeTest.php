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

namespace pocketmine\block\utils;

use PHPUnit\Framework\TestCase;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use function array_sum;
use function array_map;

class VoxelShapeTest extends TestCase{

	private static function volume(VoxelShape $shape) : float{
		return array_sum(array_map(fn(AxisAlignedBB $bb) => $bb->getVolume(), $shape->getBoxes()));
	}

	/** Slab-like shape hugging the given face. */
	private static function slabOn(int $facing) : VoxelShape{
		return VoxelShape::fromBoxes(AxisAlignedBB::one()->trim(Facing::opposite($facing), 0.5));
	}

	public function testEmpty() : void{
		self::assertTrue(VoxelShape::empty()->isEmpty());
		self::assertTrue(VoxelShape::fromBoxes()->isEmpty());
		self::assertTrue(VoxelShape::box(0, 0, 0, 1, 0, 1)->isEmpty(), "zero volume boxes are ignored");
		self::assertNull(VoxelShape::empty()->getBounds());
		self::assertSame([], VoxelShape::empty()->getBoxes());
	}

	public function testFullCubeNormalizes() : void{
		$split = VoxelShape::fromBoxes(
			new AxisAlignedBB(0, 0, 0, 0.5, 1, 1),
			new AxisAlignedBB(0.5, 0, 0, 1, 1, 1)
		);
		self::assertTrue($split->isFullCube());
		self::assertTrue($split->equals(VoxelShape::fullCube()));
		self::assertCount(1, $split->getBoxes());
	}

	public function testOverlappingBoxesUnion() : void{
		$shape = VoxelShape::fromBoxes(
			new AxisAlignedBB(0, 0, 0, 1, 0.5, 1),
			new AxisAlignedBB(0, 0.25, 0, 1, 1, 1)
		);
		self::assertTrue($shape->isFullCube());

		$union = self::slabOn(Facing::DOWN)->union(self::slabOn(Facing::UP));
		self::assertTrue($union->isFullCube());
		self::assertTrue(VoxelShape::empty()->union($union)->equals($union));
	}

	public function testStairVolume() : void{
		$stair = self::slabOn(Facing::DOWN)->union(VoxelShape::box(0, 0.5, 0, 1, 1, 0.5));
		self::assertEqualsWithDelta(0.75, self::volume($stair), 0.000001);
		self::assertCount(2, $stair->getBoxes());
	}

	public function testFaceFull() : void{
		$slab = self::slabOn(Facing::DOWN);
		self::assertTrue($slab->isFaceFull(Facing::DOWN));
		self::assertFalse($slab->isFaceFull(Facing::UP));
		foreach(Facing::HORIZONTAL as $facing){
			self::assertFalse($slab->isFaceFull($facing));
		}

		//fence post style: sticks out above the cell, top face of the cell is not covered
		$post = VoxelShape::box(0.375, 0, 0.375, 0.625, 1.5, 0.625);
		self::assertFalse($post->isFaceFull(Facing::UP));
		self::assertFalse($post->isFaceFull(Facing::DOWN));

		//taller than the cell but still covering the whole boundary square
		$tall = VoxelShape::box(0, 0, 0, 1, 1.5, 1);
		self::assertTrue($tall->isFaceFull(Facing::UP));
		self::assertTrue($tall->isFaceFull(Facing::NORTH));

		foreach(Facing::ALL as $facing){
			self::assertTrue(VoxelShape::fullCube()->isFaceFull($facing));
			self::assertFalse(VoxelShape::empty()->isFaceFull($facing));
		}
	}

	public function testRotateMatchesFacing() : void{
		foreach([Axis::X, Axis::Y, Axis::Z] as $axis){
			foreach(Facing::ALL as $facing){
				if(Facing::axis($facing) === $axis){
					continue;
				}
				foreach([true, false] as $clockwise){
					$rotated = self::slabOn($facing)->rotate($axis, $clockwise);
					$expected = self::slabOn(Facing::rotate($facing, $axis, $clockwise));
					self::assertTrue($rotated->equals($expected), "Rotating slab on " . Facing::toString($facing) . " around axis $axis");
				}
			}
		}
	}

	public function testRotateFullTurn() : void{
		$stair = self::slabOn(Facing::DOWN)->union(VoxelShape::box(0, 0.5, 0, 1, 1, 0.5));
		foreach([Axis::X, Axis::Y, Axis::Z] as $axis){
			$shape = $stair;
			for($i = 0; $i < 4; ++$i){
				$shape = $shape->rotate($axis, true);
			}
			self::assertTrue($shape->equals($stair));
			self::assertTrue($stair->rotate($axis, true)->rotate($axis, false)->equals($stair));
		}
	}

	public function testOffsetIsImmutable() : void{
		$shape = VoxelShape::fullCube();
		$moved = $shape->offset(1, 2, 3);
		self::assertTrue($shape->isFullCube());
		self::assertEquals(new AxisAlignedBB(1, 2, 3, 2, 3, 4), $moved->getBounds());
	}

	public function testGetBoxesReturnsCopies() : void{
		$shape = VoxelShape::fullCube();
		$shape->getBoxes()[0]->offset(5, 5, 5);
		self::assertEquals(AxisAlignedBB::one(), $shape->getBoxes()[0]);
	}

	public function testIntersectsAndIntercept() : void{
		$stair = self::slabOn(Facing::DOWN)->union(VoxelShape::box(0, 0.5, 0, 1, 1, 0.5));
		self::assertTrue($stair->intersectsWith(new AxisAlignedBB(0.4, 0.6, 0.1, 0.6, 0.8, 0.3)));
		self::assertFalse($stair->intersectsWith(new AxisAlignedBB(0.4, 0.6, 0.6, 0.6, 0.8, 0.9)));

		//ray going down through the open half of the stair hits the lower step's top face
		$hit = $stair->calculateIntercept(new Vector3(0.5, 2, 0.75), new Vector3(0.5, -1, 0.75));
		self::assertNotNull($hit);
		self::assertSame(Facing::UP, $hit->getHitFace());
		self::assertEqualsWithDelta(0.5, $hit->getHitVector()->y, 0.000001);
	}

	public function testCellAccess() : void{
		$stair = self::slabOn(Facing::DOWN)->union(VoxelShape::box(0, 0.5, 0, 1, 1, 0.5));
		self::assertSame([0.0, 1.0], $stair->getXPlanes());
		self::assertSame([0.0, 0.5, 1.0], $stair->getYPlanes());
		self::assertSame([0.0, 0.5, 1.0], $stair->getZPlanes());
		self::assertTrue($stair->isCellFilled(0, 0, 1));
		self::assertTrue($stair->isCellFilled(0, 1, 0));
		self::assertFalse($stair->isCellFilled(0, 1, 1));

		$this->expectException(\InvalidArgumentException::class);
		$stair->isCellFilled(1, 0, 0);
	}

	/**
	 * The default shape must cover exactly the same space as the legacy collision boxes, for every block state.
	 */
	public function testBlockShapesMatchCollisionBoxes() : void{
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block){
			$shape = $block->getCollisionShape();
			$offset = $block->getModelPositionOffset();
			if($offset !== null){
				//same addition order as getCollisionBoxes(), so the floats come out identical
				$shape = $shape->offset($offset->x, $offset->y, $offset->z);
			}
			$fromBoxes = VoxelShape::fromBoxes(...$block->getCollisionBoxes());
			self::assertTrue($shape->equals($fromBoxes), "Shape mismatch for $block: $shape vs $fromBoxes");
		}
	}

	public function testBlockShapeFollowsStateChange() : void{
		$slab = VanillaBlocks::OAK_SLAB()->setSlabType(SlabType::BOTTOM);
		self::assertTrue($slab->getCollisionShape()->isFaceFull(Facing::DOWN));
		self::assertFalse($slab->getCollisionShape()->isFaceFull(Facing::UP));

		self::assertTrue(VanillaBlocks::STONE()->getCollisionShape()->isFullCube());
		self::assertTrue(VanillaBlocks::AIR()->getCollisionShape()->isEmpty());
	}
}
