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

namespace pocketmine\data\bedrock\item\upgrade;

use pocketmine\data\bedrock\upgrade\item\ItemUpdaters;
use pocketmine\nbt\tag\CompoundTag;
use function mb_strtolower;

/**
 * Upgrades old item string IDs and metas to newer ones using the version based updater chain.
 */
final class ItemIdMetaUpgrader{
	private const TAG_NAME = "Name";
	private const TAG_DAMAGE = "Damage";

	/**
	 * @phpstan-return array{string, int}
	 */
	public function upgrade(string $id, int $meta) : array{
		$lowerId = mb_strtolower($id, 'US-ASCII');

		$tag = new CompoundTag();
		$tag->setString(self::TAG_NAME, $lowerId);
		$tag->setShort(self::TAG_DAMAGE, $meta);

		$upgraded = ItemUpdaters::updateItem($tag, 0);

		$newId = $upgraded->getString(self::TAG_NAME);
		if($newId === $lowerId){
			//nothing matched, keep the original ID with its original case
			$newId = $id;
		}

		return [$newId, $upgraded->getShort(self::TAG_DAMAGE)];
	}
}
