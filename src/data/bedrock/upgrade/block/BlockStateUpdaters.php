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

namespace pocketmine\data\bedrock\upgrade\block;

use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\CompoundTag;

final class BlockStateUpdaters{

	private static ?CompoundTagUpdaterContext $context = null;

	private function __construct(){
		//NOOP
	}

	public static function getContext() : CompoundTagUpdaterContext{
		if(self::$context === null){
			$context = new CompoundTagUpdaterContext();

			/** @var Updater[] $updaters */
			$updaters = [
				BlockStateUpdater_1_10_0::getInstance(),
				BlockStateUpdater_1_12_0::getInstance(),
				BlockStateUpdater_1_13_0::getInstance(),
				BlockStateUpdater_1_14_0::getInstance(),
				BlockStateUpdater_1_15_0::getInstance(),
				BlockStateUpdater_1_16_0::getInstance(),
				BlockStateUpdater_1_16_210::getInstance(),
				BlockStateUpdater_1_17_30::getInstance(),
				BlockStateUpdater_1_17_40::getInstance(),
				BlockStateUpdater_1_18_10::getInstance(),
				BlockStateUpdater_1_18_30::getInstance(),
				BlockStateUpdater_1_19_0::getInstance(),
				BlockStateUpdater_1_19_20::getInstance(),
				BlockStateUpdater_1_19_70::getInstance(),
				BlockStateUpdater_1_19_80::getInstance(),
				BlockStateUpdater_1_20_0::getInstance(),
				BlockStateUpdater_1_20_10::getInstance(),
				BlockStateUpdater_1_20_30::getInstance(),
				BlockStateUpdater_1_20_40::getInstance(),
				BlockStateUpdater_1_20_50::getInstance(),
				BlockStateUpdater_1_20_60::getInstance(),
				BlockStateUpdater_1_20_70::getInstance(),
				BlockStateUpdater_1_20_80::getInstance(),
				BlockStateUpdater_1_21_0::getInstance(),
				BlockStateUpdater_1_21_10::getInstance(),
				BlockStateUpdater_1_21_20::getInstance(),
				BlockStateUpdater_1_21_30::getInstance(),
				BlockStateUpdater_1_21_40::getInstance(),
				BlockStateUpdater_1_21_60::getInstance(),
				BlockStateUpdater_1_21_110::getInstance(),
			];
			foreach($updaters as $updater){
				$updater->registerUpdaters($context);
			}

			self::$context = $context;
		}
		return self::$context;
	}

	public static function updateBlockState(CompoundTag $tag, int $version) : CompoundTag{
		return self::getContext()->update($tag, $version);
	}

	public static function getLatestVersion() : int{
		return self::getContext()->getLatestVersion();
	}
}
