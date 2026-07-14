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

namespace pocketmine\plugin;

use pocketmine\utils\LegacyEnumShimTrait;
use function mb_strtolower;

/**
 * TODO: These tags need to be removed once we get rid of LegacyEnumShimTrait (PM6)
 *  These are retained for backwards compatibility only.
 *
 * @method static PluginEnableOrder POSTWORLD()
 * @method static PluginEnableOrder STARTUP()
 */
enum PluginEnableOrder{
	use LegacyEnumShimTrait;

	case STARTUP;
	case POSTWORLD;

	public static function fromString(string $name) : ?self{
		/**
		 * @var self[]|null $aliasMap
		 * @phpstan-var array<string, self>|null $aliasMap
		 */
		static $aliasMap = null;

		if($aliasMap === null){
			$aliasMap = [];
			foreach(self::cases() as $case){
				foreach($case->getAliases() as $alias){
					$aliasMap[$alias] = $case;
				}
			}
		}
		return $aliasMap[mb_strtolower($name)] ?? null;
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getAliases() : array{
		return match($this){
			self::STARTUP => ["startup"],
			self::POSTWORLD => ["postworld"]
		};
	}
}
