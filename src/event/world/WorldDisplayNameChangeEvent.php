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

namespace pocketmine\event\world;

use pocketmine\world\World;

/**
 * Called when a world's display name is changed.
 */
final class WorldDisplayNameChangeEvent extends WorldEvent{

	public function __construct(
		World $world,
		private string $oldName,
		private string $newName
	){
		parent::__construct($world);
	}

	public function getOldName() : string{
		return $this->oldName;
	}

	public function getNewName() : string{
		return $this->newName;
	}
}
