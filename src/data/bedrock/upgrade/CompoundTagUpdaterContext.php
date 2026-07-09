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

namespace pocketmine\data\bedrock\upgrade;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use function count;
use function usort;

final class CompoundTagUpdaterContext{

	/** @var CompoundTagUpdater[] */
	private array $updaters = [];

	private static function mergeVersions(int $baseVersion, int $updaterVersion) : int{
		return $updaterVersion | $baseVersion;
	}

	private static function baseVersion(int $version) : int{
		return $version & 0xFFFFFF00;
	}

	public static function updaterVersion(int $version) : int{
		return $version & 0x000000FF;
	}

	public static function makeVersion(int $major, int $minor, int $patch) : int{
		return ($major << 24) | ($minor << 16) | ($patch << 8);
	}

	public function addUpdater(int $major, int $minor, int $patch, bool $resetVersion = false, bool $bumpVersion = true) : CompoundTagUpdaterBuilder{
		$version = self::makeVersion($major, $minor, $patch);
		$prevUpdater = $this->getLatestUpdater();

		if($resetVersion || $prevUpdater === null || self::baseVersion($prevUpdater->getVersion()) !== $version){
			$updaterVersion = 0;
		}else{
			$updaterVersion = self::updaterVersion($prevUpdater->getVersion());
			if($bumpVersion){
				$updaterVersion++;
			}
		}
		$version = self::mergeVersions($version, $updaterVersion);

		$updater = new CompoundTagUpdater($version);
		$this->updaters[] = $updater;
		usort($this->updaters, fn(CompoundTagUpdater $a, CompoundTagUpdater $b) => $a->getVersion() <=> $b->getVersion());
		return $updater->builder();
	}

	public function update(CompoundTag $tag, int $version) : CompoundTag{
		$updated = $this->updateStates0($tag, $version);

		if($updated === null){
			if($version !== $this->getLatestVersion()){
				$tag = clone $tag;
				$tag->setTag("version", new IntTag($this->getLatestVersion()));
			}
			return $tag;
		}

		$updated->setTag("version", new IntTag($this->getLatestVersion()));
		return $updated;
	}

	public function updateStates(CompoundTag $tag, int $version) : CompoundTag{
		return $this->updateStates0($tag, $version) ?? $tag;
	}

	private function updateStates0(CompoundTag $tag, int $version) : ?CompoundTag{
		$mutableTag = null;
		$updated = false;
		foreach($this->updaters as $updater){
			if($updater->getVersion() < $version){
				continue;
			}

			$mutableTag ??= clone $tag;
			$updated = $updater->update($mutableTag) || $updated;
		}

		if($mutableTag === null || !$updated){
			return null;
		}
		return $mutableTag;
	}

	private function getLatestUpdater() : ?CompoundTagUpdater{
		return count($this->updaters) === 0 ? null : $this->updaters[count($this->updaters) - 1];
	}

	public function getLatestVersion() : int{
		$updater = $this->getLatestUpdater();
		return $updater === null ? 0 : $updater->getVersion();
	}
}
