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

namespace pocketmine\command\utils;

use PHPUnit\Framework\TestCase;

class CommandStringHelperTest extends TestCase{

	public static function parseQuoteAwareProvider() : \Generator{
		yield [
			'give "steve jobs" apple',
			['give', 'steve jobs', 'apple']
		];
		yield [
			'say \"escaped\"',
			['say', '"escaped"']
		];
		yield [
			'say This message contains \"escaped quotes\", which are ignored',
			['say', 'This', 'message', 'contains', '"escaped', 'quotes",', 'which', 'are', 'ignored']
		];
		yield [
			'say dontspliton"half"wayquotes',
			['say', 'dontspliton"half"wayquotes']
		];
	}

	/**
	 * @dataProvider parseQuoteAwareProvider
	 * @param string[] $expected
	 */
	public function testParseQuoteAware(string $commandLine, array $expected) : void{
		$actual = CommandStringHelper::parseQuoteAware($commandLine);

		self::assertSame($expected, $actual);
	}
}
