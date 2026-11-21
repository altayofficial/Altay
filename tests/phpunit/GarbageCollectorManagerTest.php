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

use PHPUnit\Framework\TestCase;
use function gc_collect_cycles;
use function gc_enable;

final class GarbageCollectorManagerTest extends TestCase{

	protected function tearDown() : void{
		gc_enable();
	}

	/**
	 * Puts live objects in the root buffer, which a collection has to scan without freeing anything.
	 *
	 * @return \stdClass[]
	 */
	private static function addLiveRoots(int $count) : array{
		$kept = [];
		for($i = 0; $i < $count; ++$i){
			$object = new \stdClass();
			$copy = $object;
			$kept[] = $object;
			unset($copy);
		}
		return $kept;
	}

	private static function addGarbageCycles(int $count) : void{
		for($i = 0; $i < $count; ++$i){
			$object = new \stdClass();
			$object->self = $object;
			unset($object);
		}
	}

	public function testThresholdGrowsGeometricallyWhenNothingIsCollected() : void{
		gc_collect_cycles();
		$manager = new GarbageCollectorManager(new \PrefixedLogger(\GlobalLogger::get(), "test"), null);
		$start = $manager->getThreshold();

		$kept = self::addLiveRoots($start);
		self::assertSame(0, $manager->maybeCollectCycles());
		self::assertSame($start * 2, $manager->getThreshold());

		$kept = [...$kept, ...self::addLiveRoots($start * 2)];
		self::assertSame(0, $manager->maybeCollectCycles());
		self::assertSame($start * 4, $manager->getThreshold());
	}

	public function testThresholdShrinksWhenCyclesAreCollected() : void{
		gc_collect_cycles();
		$manager = new GarbageCollectorManager(new \PrefixedLogger(\GlobalLogger::get(), "test"), null);
		$start = $manager->getThreshold();

		$kept = self::addLiveRoots($start);
		$manager->maybeCollectCycles();
		$kept = [...$kept, ...self::addLiveRoots($start * 2)];
		$manager->maybeCollectCycles();
		self::assertSame($start * 4, $manager->getThreshold());

		self::addGarbageCycles($start * 4);
		self::assertGreaterThan(0, $manager->maybeCollectCycles());
		self::assertSame($start * 2, $manager->getThreshold());

		self::addGarbageCycles($start * 2);
		$manager->maybeCollectCycles();
		self::assertSame($start, $manager->getThreshold());

		self::addGarbageCycles($start);
		$manager->maybeCollectCycles();
		self::assertSame($start, $manager->getThreshold(), "threshold should not go below the default");
	}
}
