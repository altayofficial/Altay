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

use PHPUnit\Framework\TestCase;

class SignTextTest extends TestCase{

	public function testConstructorOmitLines() : void{
		$text = new SignText([1 => "test"]);
		self::assertSame("", $text->getLine(0));
		self::assertSame("test", $text->getLine(1));
		self::assertSame("", $text->getLine(2));
		self::assertSame("", $text->getLine(3));
	}
}
