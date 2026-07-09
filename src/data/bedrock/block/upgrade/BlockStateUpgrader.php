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

namespace pocketmine\data\bedrock\block\upgrade;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\upgrade\block\BlockStateUpdaters;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Utils;
use function max;

final class BlockStateUpgrader{

	public function upgrade(BlockStateData $blockStateData) : BlockStateData{
		$version = $blockStateData->getVersion();
		$latestVersion = BlockStateUpdaters::getLatestVersion();
		if($version >= $latestVersion){
			return $blockStateData;
		}

		$states = new CompoundTag();
		foreach(Utils::stringifyKeys($blockStateData->getStates()) as $name => $tag){
			$states->setTag($name, clone $tag);
		}
		$tag = new CompoundTag();
		$tag->setString(BlockStateData::TAG_NAME, $blockStateData->getName());
		$tag->setTag(BlockStateData::TAG_STATES, $states);

		$upgraded = BlockStateUpdaters::getContext()->updateStates($tag, $version);

		$newStates = [];
		$statesTag = $upgraded->getCompoundTag(BlockStateData::TAG_STATES);
		if($statesTag !== null){
			foreach($statesTag as $name => $stateTag){
				$newStates[$name] = $stateTag;
			}
		}

		return new BlockStateData(
			$upgraded->getString(BlockStateData::TAG_NAME),
			$newStates,
			max($version, $latestVersion)
		);
	}
}
