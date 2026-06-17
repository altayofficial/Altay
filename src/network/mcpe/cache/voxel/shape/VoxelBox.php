<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\cache\voxel\shape;

/**
 * A single axis-aligned box used to build a {@link \pocketmine\network\mcpe\protocol\types\SerializableVoxelShape}.
 *
 * Coordinates are expressed in block-sixteenths (0-16), matching the units used by Bedrock geometry/voxel shapes.
 */
final class VoxelBox{

	public const AXIS_X = 0;
	public const AXIS_Y = 1;
	public const AXIS_Z = 2;

	/**
	 * @param int[] $min minimum coordinate per axis (0 = x, 1 = y, 2 = z)
	 * @param int[] $max maximum coordinate per axis (0 = x, 1 = y, 2 = z)
	 * @phpstan-param array{int, int, int} $min
	 * @phpstan-param array{int, int, int} $max
	 */
	public function __construct(
		private array $min,
		private array $max
	){}

	public function getMin(int $axis) : int{
		return $this->min[$axis];
	}

	public function getMax(int $axis) : int{
		return $this->max[$axis];
	}
}
