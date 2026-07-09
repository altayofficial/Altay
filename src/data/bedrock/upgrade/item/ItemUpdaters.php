<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\upgrade\item;

use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\CompoundTag;

final class ItemUpdaters{

	private static ?CompoundTagUpdaterContext $context = null;

	private function __construct(){
		//NOOP
	}

	public static function getContext() : CompoundTagUpdaterContext{
		if(self::$context === null){
			$context = new CompoundTagUpdaterContext();

			/** @var Updater[] $updaters */
			$updaters = [
				ItemUpdater_1_6_0::getInstance(),
				ItemUpdater_1_12_0::getInstance(),
				ItemUpdater_1_16_100::getInstance(),
				ItemUpdater_1_16_200::getInstance(),
				ItemUpdater_1_17_30::getInstance(),
				ItemUpdater_1_18_0::getInstance(),
				ItemUpdater_1_18_10::getInstance(),
				ItemUpdater_1_18_30::getInstance(),
				ItemUpdater_1_19_30::getInstance(),
				ItemUpdater_1_19_70::getInstance(),
				ItemUpdater_1_19_80::getInstance(),
				ItemUpdater_1_20_0::getInstance(),
				ItemUpdater_1_20_10::getInstance(),
				ItemUpdater_1_20_20::getInstance(),
				ItemUpdater_1_20_30::getInstance(),
				ItemUpdater_1_20_50::getInstance(),
				ItemUpdater_1_20_60::getInstance(),
				ItemUpdater_1_20_70::getInstance(),
				ItemUpdater_1_20_80::getInstance(),
				ItemUpdater_1_21_0::getInstance(),
				ItemUpdater_1_21_20::getInstance(),
				ItemUpdater_1_21_30::getInstance(),
				ItemUpdater_1_21_40::getInstance(),
				ItemUpdater_1_21_50::getInstance(),
				ItemUpdater_1_21_100::getInstance(),
				ItemUpdater_1_21_110::getInstance(),
				ItemUpdater_1_26_20::getInstance(),
			];
			foreach($updaters as $updater){
				$updater->registerUpdaters($context);
			}

			self::$context = $context;
		}
		return self::$context;
	}

	public static function updateItem(CompoundTag $tag, int $version) : CompoundTag{
		return self::getContext()->updateStates($tag, $version);
	}

	public static function getLatestVersion() : int{
		return self::getContext()->getLatestVersion();
	}
}
