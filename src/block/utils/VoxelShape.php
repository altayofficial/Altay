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

use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use function array_map;
use function array_reverse;
use function count;
use function implode;
use function sort;
use function str_contains;
use function str_repeat;
use function strlen;
use function substr;
use const SORT_NUMERIC;

/**
 * Immutable 3D shape made of axis-aligned cells. The planes on each axis cut space into a grid, and each cell of
 * that grid is either solid or empty. Coordinates are block-local, so the same shape can be shared by every block
 * in the same state.
 *
 * Every shape is kept normalized: empty outer layers are trimmed and identical neighbouring layers are merged. That
 * makes equals() reliable and keeps getBoxes() small.
 */
final class VoxelShape{
	private const EPSILON = 0.0000001;

	private const CELL_EMPTY = "0";
	private const CELL_SOLID = "1";

	private static ?self $empty = null;
	private static ?self $fullCube = null;

	/**
	 * Lazily built, never handed out directly since AxisAlignedBB is mutable.
	 * @var AxisAlignedBB[]|null
	 * @phpstan-var list<AxisAlignedBB>|null
	 */
	private ?array $boxes = null;

	/**
	 * @param float[] $xPlanes
	 * @param float[] $yPlanes
	 * @param float[] $zPlanes
	 * @phpstan-param list<float> $xPlanes
	 * @phpstan-param list<float> $yPlanes
	 * @phpstan-param list<float> $zPlanes
	 * @param string $cells one char per cell, x outermost and z innermost
	 */
	private function __construct(
		private readonly array $xPlanes,
		private readonly array $yPlanes,
		private readonly array $zPlanes,
		private readonly string $cells
	){}

	public static function empty() : self{
		return self::$empty ??= new self([], [], [], "");
	}

	public static function fullCube() : self{
		return self::$fullCube ??= new self([0.0, 1.0], [0.0, 1.0], [0.0, 1.0], self::CELL_SOLID);
	}

	public static function box(float $minX, float $minY, float $minZ, float $maxX, float $maxY, float $maxZ) : self{
		return self::fromBoxes(new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ));
	}

	/**
	 * Builds a shape covering the union of the given boxes. Boxes with no volume are ignored.
	 */
	public static function fromBoxes(AxisAlignedBB ...$boxes) : self{
		$solidBoxes = [];
		$xPlanes = $yPlanes = $zPlanes = [];
		foreach($boxes as $box){
			if($box->maxX - $box->minX <= self::EPSILON || $box->maxY - $box->minY <= self::EPSILON || $box->maxZ - $box->minZ <= self::EPSILON){
				continue;
			}
			$solidBoxes[] = $box;
			$xPlanes[] = $box->minX;
			$xPlanes[] = $box->maxX;
			$yPlanes[] = $box->minY;
			$yPlanes[] = $box->maxY;
			$zPlanes[] = $box->minZ;
			$zPlanes[] = $box->maxZ;
		}
		if(count($solidBoxes) === 0){
			return self::empty();
		}

		$xPlanes = self::sortedUnique($xPlanes);
		$yPlanes = self::sortedUnique($yPlanes);
		$zPlanes = self::sortedUnique($zPlanes);

		$cells = "";
		for($x = 0, $nx = count($xPlanes) - 1; $x < $nx; ++$x){
			$midX = ($xPlanes[$x] + $xPlanes[$x + 1]) / 2;
			for($y = 0, $ny = count($yPlanes) - 1; $y < $ny; ++$y){
				$midY = ($yPlanes[$y] + $yPlanes[$y + 1]) / 2;
				for($z = 0, $nz = count($zPlanes) - 1; $z < $nz; ++$z){
					$midZ = ($zPlanes[$z] + $zPlanes[$z + 1]) / 2;
					$solid = false;
					foreach($solidBoxes as $box){
						//midpoints never sit on a plane, so strict checks are fine here
						if(
							$midX > $box->minX && $midX < $box->maxX &&
							$midY > $box->minY && $midY < $box->maxY &&
							$midZ > $box->minZ && $midZ < $box->maxZ
						){
							$solid = true;
							break;
						}
					}
					$cells .= $solid ? self::CELL_SOLID : self::CELL_EMPTY;
				}
			}
		}

		return self::normalize($xPlanes, $yPlanes, $zPlanes, $cells);
	}

	/**
	 * Returns a shape covering both this shape and the other one.
	 */
	public function union(VoxelShape $other) : self{
		if($other->isEmpty()){
			return $this;
		}
		if($this->isEmpty()){
			return $other;
		}

		$xPlanes = self::sortedUnique([...$this->xPlanes, ...$other->xPlanes]);
		$yPlanes = self::sortedUnique([...$this->yPlanes, ...$other->yPlanes]);
		$zPlanes = self::sortedUnique([...$this->zPlanes, ...$other->zPlanes]);

		$cells = "";
		for($x = 0, $nx = count($xPlanes) - 1; $x < $nx; ++$x){
			$midX = ($xPlanes[$x] + $xPlanes[$x + 1]) / 2;
			for($y = 0, $ny = count($yPlanes) - 1; $y < $ny; ++$y){
				$midY = ($yPlanes[$y] + $yPlanes[$y + 1]) / 2;
				for($z = 0, $nz = count($zPlanes) - 1; $z < $nz; ++$z){
					$midZ = ($zPlanes[$z] + $zPlanes[$z + 1]) / 2;
					$solid = $this->containsPoint($midX, $midY, $midZ) || $other->containsPoint($midX, $midY, $midZ);
					$cells .= $solid ? self::CELL_SOLID : self::CELL_EMPTY;
				}
			}
		}

		return self::normalize($xPlanes, $yPlanes, $zPlanes, $cells);
	}

	/**
	 * Returns a copy of the shape moved by the given amount. Unlike AxisAlignedBB::offset(), this never modifies
	 * the original.
	 */
	public function offset(float $x, float $y, float $z) : self{
		if($this->isEmpty() || ($x === 0.0 && $y === 0.0 && $z === 0.0)){
			return $this;
		}
		return new self(
			array_map(fn(float $p) => $p + $x, $this->xPlanes),
			array_map(fn(float $p) => $p + $y, $this->yPlanes),
			array_map(fn(float $p) => $p + $z, $this->zPlanes),
			$this->cells
		);
	}

	/**
	 * Rotates the shape by 90 degrees around the centre of the block, in the same direction Facing::rotate() turns
	 * a facing. Handy for facing-based blocks: build the shape for one direction and rotate it for the rest.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function rotate(int $axis, bool $clockwise) : self{
		if($this->isEmpty()){
			return $this;
		}

		//each entry is [source axis, flip] for the new X, Y and Z axes, derived from Facing's clockwise table
		$map = match($axis){
			Axis::Y => $clockwise ?
				[[Axis::Z, true], [Axis::Y, false], [Axis::X, false]] :
				[[Axis::Z, false], [Axis::Y, false], [Axis::X, true]],
			Axis::Z => $clockwise ?
				[[Axis::Y, false], [Axis::X, true], [Axis::Z, false]] :
				[[Axis::Y, true], [Axis::X, false], [Axis::Z, false]],
			Axis::X => $clockwise ?
				[[Axis::X, false], [Axis::Z, false], [Axis::Y, true]] :
				[[Axis::X, false], [Axis::Z, true], [Axis::Y, false]],
			default => throw new \InvalidArgumentException("Invalid axis $axis")
		};

		$oldPlanes = [Axis::X => $this->xPlanes, Axis::Y => $this->yPlanes, Axis::Z => $this->zPlanes];
		$newPlanes = [];
		foreach($map as $newAxis => [$sourceAxis, $flip]){
			$newPlanes[$newAxis] = $flip ?
				array_reverse(array_map(fn(float $p) => 1 - $p, $oldPlanes[$sourceAxis])) :
				$oldPlanes[$sourceAxis];
		}
		[$xPlanes, $yPlanes, $zPlanes] = $newPlanes;

		$oldCounts = [Axis::X => count($this->xPlanes) - 1, Axis::Y => count($this->yPlanes) - 1, Axis::Z => count($this->zPlanes) - 1];
		$cells = "";
		$old = [Axis::X => 0, Axis::Y => 0, Axis::Z => 0];
		for($x = 0, $nx = count($xPlanes) - 1; $x < $nx; ++$x){
			for($y = 0, $ny = count($yPlanes) - 1; $y < $ny; ++$y){
				for($z = 0, $nz = count($zPlanes) - 1; $z < $nz; ++$z){
					foreach([$x, $y, $z] as $newAxis => $coord){
						[$sourceAxis, $flip] = $map[$newAxis];
						$old[$sourceAxis] = $flip ? $oldCounts[$sourceAxis] - 1 - $coord : $coord;
					}
					$cells .= $this->cells[$this->index($old[Axis::X], $old[Axis::Y], $old[Axis::Z])];
				}
			}
		}

		//a quarter turn just shuffles the grid, it can't produce new mergeable layers
		return new self($xPlanes, $yPlanes, $zPlanes, $cells);
	}

	public function isEmpty() : bool{
		return $this->cells === "";
	}

	public function isFullCube() : bool{
		return $this->equals(self::fullCube());
	}

	/**
	 * Returns whether the shape completely covers the given face of the block cell, i.e. the 1x1 square on the
	 * block boundary. Parts sticking out beyond the cell (like the top of a fence) don't count.
	 */
	public function isFaceFull(int $facing) : bool{
		return $this->coversFace($facing, 0, 0, 1, 1);
	}

	/**
	 * Returns whether the shape covers the given rectangle of a block face, right at the block boundary. U and V
	 * are the two axes perpendicular to the face, in X, Y, Z order (so X and Z for the top face).
	 *
	 * @throws \InvalidArgumentException
	 */
	public function coversFace(int $facing, float $minU, float $minV, float $maxU, float $maxV) : bool{
		Facing::validate($facing);
		if($minU >= $maxU || $minV >= $maxV){
			throw new \InvalidArgumentException("Face region must have a positive area");
		}
		if($this->isEmpty()){
			return false;
		}

		$axis = Facing::axis($facing);
		$planes = $this->getPlanes($axis);
		$boundary = Facing::isPositive($facing) ? 1.0 : 0.0;

		//ok so the boundary isn't always a plane, normalize merges it away when the shape keeps going past the cell
		$layer = null;
		for($i = 0, $n = count($planes) - 1; $i < $n; ++$i){
			$touches = Facing::isPositive($facing) ?
				$planes[$i] < $boundary - self::EPSILON && $planes[$i + 1] >= $boundary - self::EPSILON :
				$planes[$i] <= $boundary + self::EPSILON && $planes[$i + 1] > $boundary + self::EPSILON;
			if($touches){
				$layer = $i;
				break;
			}
		}
		if($layer === null){
			return false;
		}

		[$uAxis, $vAxis] = match($axis){
			Axis::X => [Axis::Y, Axis::Z],
			Axis::Y => [Axis::X, Axis::Z],
			Axis::Z => [Axis::X, Axis::Y],
			default => throw new \AssertionError("Unreachable")
		};
		$uPlanes = $this->getPlanes($uAxis);
		$vPlanes = $this->getPlanes($vAxis);
		if(
			$uPlanes[0] > $minU + self::EPSILON || $uPlanes[count($uPlanes) - 1] < $maxU - self::EPSILON ||
			$vPlanes[0] > $minV + self::EPSILON || $vPlanes[count($vPlanes) - 1] < $maxV - self::EPSILON
		){
			return false;
		}

		$coords = [Axis::X => 0, Axis::Y => 0, Axis::Z => 0];
		$coords[$axis] = $layer;
		for($u = 0, $nu = count($uPlanes) - 1; $u < $nu; ++$u){
			if($uPlanes[$u + 1] <= $minU + self::EPSILON || $uPlanes[$u] >= $maxU - self::EPSILON){
				continue;
			}
			$coords[$uAxis] = $u;
			for($v = 0, $nv = count($vPlanes) - 1; $v < $nv; ++$v){
				if($vPlanes[$v + 1] <= $minV + self::EPSILON || $vPlanes[$v] >= $maxV - self::EPSILON){
					continue;
				}
				$coords[$vAxis] = $v;
				if($this->cells[$this->index($coords[Axis::X], $coords[Axis::Y], $coords[Axis::Z])] !== self::CELL_SOLID){
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Returns the smallest box enclosing the whole shape, or null if the shape is empty.
	 */
	public function getBounds() : ?AxisAlignedBB{
		if($this->isEmpty()){
			return null;
		}
		return new AxisAlignedBB(
			$this->xPlanes[0], $this->yPlanes[0], $this->zPlanes[0],
			$this->xPlanes[count($this->xPlanes) - 1], $this->yPlanes[count($this->yPlanes) - 1], $this->zPlanes[count($this->zPlanes) - 1]
		);
	}

	/**
	 * Returns the shape as a list of non-overlapping boxes. The boxes are fresh copies, so callers are free to
	 * modify them.
	 *
	 * @return AxisAlignedBB[]
	 * @phpstan-return list<AxisAlignedBB>
	 */
	public function getBoxes() : array{
		return array_map(fn(AxisAlignedBB $bb) => clone $bb, $this->boxes ??= $this->computeBoxes());
	}

	public function intersectsWith(AxisAlignedBB $bb, float $epsilon = 0.00001) : bool{
		foreach($this->boxes ??= $this->computeBoxes() as $box){
			if($box->intersectsWith($bb, $epsilon)){
				return true;
			}
		}
		return false;
	}

	/**
	 * Performs a ray trace between the two positions against the shape, returning the hit closest to pos1.
	 */
	public function calculateIntercept(Vector3 $pos1, Vector3 $pos2) : ?RayTraceResult{
		$closest = null;
		$closestDistance = 0.0;
		foreach($this->getBoxes() as $box){
			$hit = $box->calculateIntercept($pos1, $pos2);
			if($hit === null){
				continue;
			}
			$distance = $hit->hitVector->distanceSquared($pos1);
			if($closest === null || $distance < $closestDistance){
				$closest = $hit;
				$closestDistance = $distance;
			}
		}
		return $closest;
	}

	public function equals(VoxelShape $other) : bool{
		return
			$this->cells === $other->cells &&
			$this->xPlanes === $other->xPlanes &&
			$this->yPlanes === $other->yPlanes &&
			$this->zPlanes === $other->zPlanes;
	}

	/**
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	public function getXPlanes() : array{ return $this->xPlanes; }

	/**
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	public function getYPlanes() : array{ return $this->yPlanes; }

	/**
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	public function getZPlanes() : array{ return $this->zPlanes; }

	/**
	 * Returns whether the cell between planes [x, x+1], [y, y+1] and [z, z+1] is solid.
	 */
	public function isCellFilled(int $x, int $y, int $z) : bool{
		if(
			$x < 0 || $x >= count($this->xPlanes) - 1 ||
			$y < 0 || $y >= count($this->yPlanes) - 1 ||
			$z < 0 || $z >= count($this->zPlanes) - 1
		){
			throw new \InvalidArgumentException("Cell ($x, $y, $z) is outside the shape grid");
		}
		return $this->cells[$this->index($x, $y, $z)] === self::CELL_SOLID;
	}

	public function __toString() : string{
		return "VoxelShape(" . implode(", ", array_map(fn(AxisAlignedBB $bb) => (string) $bb, $this->getBoxes())) . ")";
	}

	/**
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	private function getPlanes(int $axis) : array{
		return match($axis){
			Axis::X => $this->xPlanes,
			Axis::Y => $this->yPlanes,
			Axis::Z => $this->zPlanes,
			default => throw new \InvalidArgumentException("Invalid axis $axis")
		};
	}

	private function index(int $x, int $y, int $z) : int{
		return ($x * (count($this->yPlanes) - 1) + $y) * (count($this->zPlanes) - 1) + $z;
	}

	private function containsPoint(float $x, float $y, float $z) : bool{
		$cx = self::findCell($this->xPlanes, $x);
		$cy = self::findCell($this->yPlanes, $y);
		$cz = self::findCell($this->zPlanes, $z);
		return $cx !== null && $cy !== null && $cz !== null && $this->cells[$this->index($cx, $cy, $cz)] === self::CELL_SOLID;
	}

	/**
	 * @param float[] $planes
	 * @phpstan-param list<float> $planes
	 */
	private static function findCell(array $planes, float $value) : ?int{
		$low = 0;
		$high = count($planes) - 2;
		if($high < 0 || $value < $planes[0] || $value > $planes[$high + 1]){
			return null;
		}
		while($low < $high){
			$mid = ($low + $high + 1) >> 1;
			if($planes[$mid] <= $value){
				$low = $mid;
			}else{
				$high = $mid - 1;
			}
		}
		return $low;
	}

	/**
	 * Greedy meshing: grow each unclaimed solid cell along z, then y, then x.
	 *
	 * @return AxisAlignedBB[]
	 * @phpstan-return list<AxisAlignedBB>
	 */
	private function computeBoxes() : array{
		if($this->isEmpty()){
			return [];
		}
		$nx = count($this->xPlanes) - 1;
		$ny = count($this->yPlanes) - 1;
		$nz = count($this->zPlanes) - 1;
		$free = $this->cells; //claimed cells get flipped to empty as we go

		$boxes = [];
		for($x = 0; $x < $nx; ++$x){
			for($y = 0; $y < $ny; ++$y){
				for($z = 0; $z < $nz; ++$z){
					if($free[$this->index($x, $y, $z)] !== self::CELL_SOLID){
						continue;
					}

					$z2 = $z + 1;
					while($z2 < $nz && $free[$this->index($x, $y, $z2)] === self::CELL_SOLID){
						++$z2;
					}

					$y2 = $y + 1;
					while($y2 < $ny && $this->isRegionFree($free, $x, $x + 1, $y2, $y2 + 1, $z, $z2)){
						++$y2;
					}

					$x2 = $x + 1;
					while($x2 < $nx && $this->isRegionFree($free, $x2, $x2 + 1, $y, $y2, $z, $z2)){
						++$x2;
					}

					for($cx = $x; $cx < $x2; ++$cx){
						for($cy = $y; $cy < $y2; ++$cy){
							for($cz = $z; $cz < $z2; ++$cz){
								$free[$this->index($cx, $cy, $cz)] = self::CELL_EMPTY;
							}
						}
					}

					$boxes[] = new AxisAlignedBB(
						$this->xPlanes[$x], $this->yPlanes[$y], $this->zPlanes[$z],
						$this->xPlanes[$x2], $this->yPlanes[$y2], $this->zPlanes[$z2]
					);
				}
			}
		}

		return $boxes;
	}

	private function isRegionFree(string $free, int $x1, int $x2, int $y1, int $y2, int $z1, int $z2) : bool{
		for($x = $x1; $x < $x2; ++$x){
			for($y = $y1; $y < $y2; ++$y){
				for($z = $z1; $z < $z2; ++$z){
					if($free[$this->index($x, $y, $z)] !== self::CELL_SOLID){
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @param float[] $values
	 * @phpstan-param list<float> $values
	 * @return float[]
	 * @phpstan-return list<float>
	 */
	private static function sortedUnique(array $values) : array{
		sort($values, SORT_NUMERIC);
		$result = [];
		foreach($values as $value){
			//hmm, offsets love to produce 0.30000000000000004, don't let that turn into a sliver cell
			if(count($result) === 0 || $value - $result[count($result) - 1] > self::EPSILON){
				$result[] = $value;
			}
		}
		return $result;
	}

	/**
	 * Trims empty outer layers and merges identical neighbouring layers on every axis.
	 *
	 * @param float[] $xPlanes
	 * @param float[] $yPlanes
	 * @param float[] $zPlanes
	 * @phpstan-param list<float> $xPlanes
	 * @phpstan-param list<float> $yPlanes
	 * @phpstan-param list<float> $zPlanes
	 */
	private static function normalize(array $xPlanes, array $yPlanes, array $zPlanes, string $cells) : self{
		if(!str_contains($cells, self::CELL_SOLID)){
			return self::empty();
		}
		$nx = count($xPlanes) - 1;
		$ny = count($yPlanes) - 1;
		$nz = count($zPlanes) - 1;

		$xLayers = [];
		for($x = 0; $x < $nx; ++$x){
			$xLayers[] = substr($cells, $x * $ny * $nz, $ny * $nz);
		}
		$yLayers = [];
		for($y = 0; $y < $ny; ++$y){
			$layer = "";
			for($x = 0; $x < $nx; ++$x){
				$layer .= substr($cells, ($x * $ny + $y) * $nz, $nz);
			}
			$yLayers[] = $layer;
		}
		$zLayers = [];
		for($z = 0; $z < $nz; ++$z){
			$layer = "";
			for($i = $z, $total = strlen($cells); $i < $total; $i += $nz){
				$layer .= $cells[$i];
			}
			$zLayers[] = $layer;
		}

		//merging along one axis keeps layer equality on the others intact, so each axis can be done on its own
		[$xKeep, $newXPlanes] = self::mergeLayers($xLayers, $xPlanes);
		[$yKeep, $newYPlanes] = self::mergeLayers($yLayers, $yPlanes);
		[$zKeep, $newZPlanes] = self::mergeLayers($zLayers, $zPlanes);

		$newCells = "";
		foreach($xKeep as $x){
			foreach($yKeep as $y){
				foreach($zKeep as $z){
					$newCells .= $cells[($x * $ny + $y) * $nz + $z];
				}
			}
		}

		return new self($newXPlanes, $newYPlanes, $newZPlanes, $newCells);
	}

	/**
	 * @param string[] $layers
	 * @param float[]  $planes
	 * @phpstan-param list<string> $layers
	 * @phpstan-param list<float>  $planes
	 * @return array{list<int>, list<float>} indices of the layers to keep, and the planes bounding them
	 */
	private static function mergeLayers(array $layers, array $planes) : array{
		$emptyLayer = str_repeat(self::CELL_EMPTY, strlen($layers[0]));
		$first = 0;
		$last = count($layers) - 1;
		while($layers[$first] === $emptyLayer){
			++$first;
		}
		while($layers[$last] === $emptyLayer){
			--$last;
		}

		$keep = [$first];
		$newPlanes = [$planes[$first]];
		for($i = $first + 1; $i <= $last; ++$i){
			if($layers[$i] !== $layers[$i - 1]){
				$keep[] = $i;
				$newPlanes[] = $planes[$i];
			}
		}
		$newPlanes[] = $planes[$last + 1];

		return [$keep, $newPlanes];
	}
}
