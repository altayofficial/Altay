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

namespace pocketmine\utils;

use PHPUnit\Framework\TestCase;

final class CloningRegistryTraitTest extends TestCase{

	/**
	 * @phpstan-return \Generator<int, array{\Closure() : \stdClass}, void, void>
	 */
	public static function cloningRegistryMembersProvider() : \Generator{
		yield [function() : \stdClass{ return TestCloningRegistry::TEST1(); }];
		yield [function() : \stdClass{ return TestCloningRegistry::TEST2(); }];
		yield [function() : \stdClass{ return TestCloningRegistry::TEST3(); }];
	}

	/**
	 * @dataProvider cloningRegistryMembersProvider
	 * @phpstan-param \Closure() : \stdClass $provider
	 */
	public function testEachMemberClone(\Closure $provider) : void{
		self::assertNotSame($provider(), $provider(), "Cloning registry should never return the same object twice");
	}

	public function testGetAllClone() : void{
		$list1 = TestCloningRegistry::getAll();
		$list2 = TestCloningRegistry::getAll();
		foreach(Utils::stringifyKeys($list1) as $k => $member){
			self::assertNotSame($member, $list2[$k], "VanillaBlocks ought to clone its members");
		}
	}
}
