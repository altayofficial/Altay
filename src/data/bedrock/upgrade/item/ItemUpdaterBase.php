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

use pocketmine\data\bedrock\upgrade\CompoundTagEditHelper;
use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Utils;

abstract class ItemUpdaterBase implements Updater{

	/**
	 * @phpstan-return array{int, int, int}
	 */
	abstract protected function getVersion() : array;

	/**
	 * @return string[]
	 * @phpstan-return array<string, string>
	 */
	protected function getRenamedIds() : array{
		return [];
	}

	/**
	 * @return string[][]
	 * @phpstan-return array<string, array<int, string>>
	 */
	protected function getRemappedMetas() : array{
		return [];
	}

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		[$major, $minor, $patch] = $this->getVersion();

		foreach(Utils::stringifyKeys($this->getRemappedMetas()) as $oldId => $metaMap){
			foreach($metaMap as $meta => $newId){
				$context->addUpdater($major, $minor, $patch)
					->match("Name", $oldId)
					->match("Damage", (string) $meta)
					->edit("Name", function(CompoundTagEditHelper $helper) use ($newId) : void{
						$helper->replaceWith("Name", new StringTag($newId));
					})
					->edit("Damage", function(CompoundTagEditHelper $helper) : void{
						$helper->replaceWith("Damage", new ShortTag(0));
					});
			}
		}

		foreach(Utils::stringifyKeys($this->getRenamedIds()) as $oldId => $newId){
			$context->addUpdater($major, $minor, $patch)
				->match("Name", $oldId)
				->edit("Name", function(CompoundTagEditHelper $helper) use ($newId) : void{
					$helper->replaceWith("Name", new StringTag($newId));
				});
		}
	}
}
