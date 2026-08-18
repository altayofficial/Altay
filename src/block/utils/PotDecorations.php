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

use pocketmine\item\PotterySherdType;

/**
 * @see \pocketmine\block\DecoratedPot
 */
final class PotDecorations{

	public function __construct(
		private readonly ?PotterySherdType $back = null,
		private readonly ?PotterySherdType $left = null,
		private readonly ?PotterySherdType $right = null,
		private readonly ?PotterySherdType $front = null
	){
		//NOOP
	}

	public function getBack() : ?PotterySherdType{ return $this->back; }

	public function getLeft() : ?PotterySherdType{ return $this->left; }

	public function getRight() : ?PotterySherdType{ return $this->right; }

	public function getFront() : ?PotterySherdType{ return $this->front; }

	/**
	 * @return (PotterySherdType|null)[]
	 * @phpstan-return list<PotterySherdType|null>
	 */
	public function toArray() : array{
		return [$this->back, $this->left, $this->right, $this->front];
	}

	/**
	 * @param (PotterySherdType|null)[] $faces
	 * @phpstan-param list<PotterySherdType|null> $faces
	 */
	public static function fromArray(array $faces) : self{
		return new self($faces[0] ?? null, $faces[1] ?? null, $faces[2] ?? null, $faces[3] ?? null);
	}

	public function isEmpty() : bool{
		return $this->back === null && $this->left === null && $this->right === null && $this->front === null;
	}

	public function equals(self $other) : bool{
		return $this->back === $other->back &&
			$this->left === $other->left &&
			$this->right === $other->right &&
			$this->front === $other->front;
	}
}
