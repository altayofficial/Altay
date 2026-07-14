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

class RandomTest extends TestCase{

	public function testNextSignedIntReturnsSignedInts() : void{
		//use a known seed which should definitely produce negatives
		$random = new Random(0);
		$negatives = false;

		for($i = 0; $i < 100; ++$i){
			if($random->nextSignedInt() < 0){
				$negatives = true;
				break;
			}
		}
		self::assertTrue($negatives);
	}
}
