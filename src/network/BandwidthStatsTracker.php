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

namespace pocketmine\network;

use function array_fill;
use function array_sum;
use function count;

final class BandwidthStatsTracker{
	/** @var int[] */
	private array $history;
	private int $nextHistoryIndex = 0;
	private int $bytesSinceLastRotation = 0;
	private int $totalBytes = 0;

	/** @phpstan-param positive-int $historySize */
	public function __construct(int $historySize){
		$this->history = array_fill(0, $historySize, 0);
	}

	public function add(int $bytes) : void{
		$this->totalBytes += $bytes;
		$this->bytesSinceLastRotation += $bytes;
	}

	public function getTotalBytes() : int{ return $this->totalBytes; }

	/**
	 * Adds the bytes tracked since the last rotation to the history array, overwriting an old entry.
	 * This should be called on a regular interval that you want to collect average measurements over
	 * (e.g. if you want bytes per second, call this every second).
	 */
	public function rotateHistory() : void{
		$this->history[$this->nextHistoryIndex] = $this->bytesSinceLastRotation;
		$this->bytesSinceLastRotation = 0;
		$this->nextHistoryIndex = ($this->nextHistoryIndex + 1) % count($this->history);
	}

	/**
	 * Returns the average of all the tracked history values.
	 */
	public function getAverageBytes() : float{
		return array_sum($this->history) / count($this->history);
	}

	public function resetHistory() : void{
		$this->history = array_fill(0, count($this->history), 0);
	}
}
