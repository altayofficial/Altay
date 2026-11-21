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

namespace pocketmine;

use pocketmine\timings\TimingsHandler;
use function gc_collect_cycles;
use function gc_disable;
use function gc_status;
use function hrtime;
use function intdiv;
use function max;
use function min;
use function number_format;
use function sprintf;

/**
 * Allows threads to manually trigger the cyclic garbage collector using a threshold like PHP's own garbage collector,
 * but triggered at a time that suits the thread instead of in random code pathways.
 *
 * The GC trigger behaviour in this class was adapted from Zend/zend_gc.c as of PHP 8.3.14.
 */
final class GarbageCollectorManager{
	//TODO: these values could be adjusted to better suit PM
	private const GC_THRESHOLD_TRIGGER = 100;
	private const GC_THRESHOLD_MAX = 1_000_000_000;
	private const GC_THRESHOLD_DEFAULT = 10_001;
	private const GC_THRESHOLD_STEP = 10_000;

	private int $threshold = self::GC_THRESHOLD_DEFAULT;
	private int $collectionTimeTotalNs = 0;
	private int $runs = 0;

	private \Logger $logger;
	private TimingsHandler $timings;

	public function __construct(
		\Logger $logger,
		?TimingsHandler $parentTimings,
	){
		gc_disable();
		$this->logger = new \PrefixedLogger($logger, "Cyclic Garbage Collector");
		$this->timings = new TimingsHandler("Cyclic Garbage Collector", $parentTimings);
	}

	private function adjustGcThreshold(int $cyclesCollected, int $rootsAfterGC) : void{
		//PHP grows the threshold by a fixed step, but a run walks everything reachable from the roots, which on a
		//server with lots of chunks loaded is most of the heap. With a fixed step we end up walking a multi-GB heap
		//every few seconds for nothing, so grow it geometrically when a run doesn't find anything worth the effort
		if($cyclesCollected < self::GC_THRESHOLD_TRIGGER || $rootsAfterGC >= $this->threshold){
			$this->threshold = min(self::GC_THRESHOLD_MAX, max($this->threshold + self::GC_THRESHOLD_STEP, $this->threshold * 2));
		}elseif($this->threshold > self::GC_THRESHOLD_DEFAULT){
			$this->threshold = max(self::GC_THRESHOLD_DEFAULT, intdiv($this->threshold, 2));
		}
	}

	public function getThreshold() : int{ return $this->threshold; }

	public function getCollectionTimeTotalNs() : int{ return $this->collectionTimeTotalNs; }

	public function maybeCollectCycles() : int{
		$rootsBefore = gc_status()["roots"];
		if($rootsBefore < $this->threshold){
			return 0;
		}

		$this->timings->startTiming();

		$start = hrtime(true);
		$cycles = gc_collect_cycles();
		$end = hrtime(true);

		$rootsAfter = gc_status()["roots"];
		$this->adjustGcThreshold($cycles, $rootsAfter);

		$this->timings->stopTiming();

		$time = $end - $start;
		$this->collectionTimeTotalNs += $time;
		$this->runs++;
		$this->logger->info(sprintf(
			"Run #%d took %s ms (%s -> %s roots, %s cycles collected) - cumulative GC time: %s ms",
			$this->runs,
			number_format($time / 1_000_000, 2),
			$rootsBefore,
			$rootsAfter,
			$cycles,
			number_format($this->collectionTimeTotalNs / 1_000_000, 2)
		));

		return $cycles;
	}
}
