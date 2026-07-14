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

namespace pocketmine\network\mcpe\cache;

use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeGroup;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;

final class CreativeInventoryCacheEntry{

	/**
	 * @param CreativeCategory[]     $categories
	 * @param CreativeGroup[]|null[] $groups
	 * @param CreativeItemEntry[]    $items
	 *
	 * @phpstan-param list<CreativeCategory>   $categories
	 * @phpstan-param list<CreativeGroup|null> $groups
	 * @phpstan-param list<CreativeItemEntry>  $items
	 */
	public function __construct(
		public readonly array $categories,
		public readonly array $groups,
		public readonly array $items,
	){
		//NOOP
	}
}
