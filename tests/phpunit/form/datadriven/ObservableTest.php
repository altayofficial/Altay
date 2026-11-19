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

namespace pocketmine\form\datadriven;

use PHPUnit\Framework\TestCase;
use pocketmine\player\Player;

final class ObservableTest extends TestCase{

	public function testListenersSeeServerChanges() : void{
		$observable = Observable::int(1);
		$seen = [];
		$observable->getListeners()->add(function(int $value, ?Player $source) use (&$seen) : void{
			$seen[] = [$value, $source];
		});

		$observable->set(5);

		self::assertSame(5, $observable->get());
		self::assertSame([[5, null]], $seen);
	}

	public function testSameValueDoesNotNotify() : void{
		$observable = Observable::string("a");
		$calls = 0;
		$observable->getListeners()->add(function(string $value, ?Player $source) use (&$calls) : void{
			$calls++;
		});

		$observable->set("a");

		self::assertSame(0, $calls);
	}

	public function testRemovedListenerIsNotCalled() : void{
		$observable = Observable::bool(false);
		$calls = 0;
		$listener = function(bool $value, ?Player $source) use (&$calls) : void{
			$calls++;
		};
		$observable->getListeners()->add($listener);
		$observable->getListeners()->remove($listener);

		$observable->set(true);

		self::assertSame(0, $calls);
	}

	public function testWrongTypeIsRejected() : void{
		$observable = Observable::string("a");

		$this->expectException(\TypeError::class);
		/** @phpstan-ignore argument.type (that's the point of the test) */
		$observable->set(1);
	}

	public function testIntDoesNotAcceptBool() : void{
		$observable = Observable::int(1);

		$this->expectException(\TypeError::class);
		/** @phpstan-ignore argument.type (that's the point of the test) */
		$observable->set(true);
	}
}
