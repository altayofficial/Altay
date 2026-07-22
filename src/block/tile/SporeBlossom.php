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

namespace pocketmine\block\tile;

use pocketmine\nbt\tag\CompoundTag;

/**
 * This exists to force the client to update the spore blossom every tick, which is necessary for it to generate
 * particles.
 */
final class SporeBlossom extends Spawnable{

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		//NOOP
	}

	public function readSaveData(CompoundTag $nbt) : void{
		//NOOP
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		//NOOP
	}
}
