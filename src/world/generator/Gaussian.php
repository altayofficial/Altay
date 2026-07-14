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

namespace pocketmine\world\generator;

use function array_sum;
use function exp;
use function sqrt;

final class Gaussian{
	/** @var float[][] */
	public array $kernel = [];

	public readonly float $weightSum;

	/** @var float[] */
	public array $kernel1D = [];

	public readonly float $weightSum1D;

	public function __construct(public int $smoothSize){
		$bellSize = 1 / $this->smoothSize;
		$bellHeight = 2 * $this->smoothSize;

		for($sx = -$this->smoothSize; $sx <= $this->smoothSize; ++$sx){
			$bx = $bellSize * $sx;

			$this->kernel1D[$sx + $this->smoothSize] = sqrt($bellHeight) * exp(-($bx * $bx) / 2);

			$this->kernel[$sx + $this->smoothSize] = [];
			for($sz = -$this->smoothSize; $sz <= $this->smoothSize; ++$sz){
				$bz = $bellSize * $sz;
				$this->kernel[$sx + $this->smoothSize][$sz + $this->smoothSize] = $bellHeight * exp(-($bx * $bx + $bz * $bz) / 2);
			}
		}

		$this->weightSum1D = array_sum($this->kernel1D);
		$this->weightSum = $this->weightSum1D ** 2;
	}
}
