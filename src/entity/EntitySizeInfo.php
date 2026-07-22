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

namespace pocketmine\entity;

use function min;

final class EntitySizeInfo{
	private float $eyeHeight;

	public function __construct(
		private float $height,
		private float $width,
		?float $eyeHeight = null
	){
		$this->eyeHeight = $eyeHeight ?? min($this->height / 2 + 0.1, $this->height);
	}

	public function getHeight() : float{ return $this->height; }

	public function getWidth() : float{ return $this->width; }

	public function getEyeHeight() : float{ return $this->eyeHeight; }

	public function scale(float $newScale) : self{
		return new self(
			$this->height * $newScale,
			$this->width * $newScale,
			$this->eyeHeight * $newScale
		);
	}
}
