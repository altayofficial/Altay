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

namespace pocketmine\item;

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;

class LegacyStringToItemParserTest extends TestCase{

	/**
	 * @return mixed[][]
	 * @phpstan-return list<array{string,Item}>
	 */
	public static function itemFromStringProvider() : array{
		return [
			["dye:4", VanillaItems::LAPIS_LAZULI()],
			["351", VanillaItems::INK_SAC()],
			["351:4", VanillaItems::LAPIS_LAZULI()],
			["stone:3", VanillaBlocks::DIORITE()->asItem()],
			["minecraft:string", VanillaItems::STRING()],
			["diamond_pickaxe", VanillaItems::DIAMOND_PICKAXE()]
		];
	}

	/**
	 * @dataProvider itemFromStringProvider
	 */
	public function testFromStringSingle(string $string, Item $expected) : void{
		$item = LegacyStringToItemParser::getInstance()->parse($string);

		self::assertTrue($item->equals($expected));
	}
}
