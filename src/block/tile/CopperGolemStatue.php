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

use pocketmine\block\utils\CopperGolemStatuePose;
use pocketmine\nbt\tag\CompoundTag;

/**
 * @deprecated
 * @see \pocketmine\block\CopperGolemStatue
 */
class CopperGolemStatue extends Spawnable{
	private const TAG_POSE = "Pose"; //TAG_Int

	private CopperGolemStatuePose $pose = CopperGolemStatuePose::STANDING;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->pose = self::poseFromId($nbt->getInt(self::TAG_POSE, 0)) ?? CopperGolemStatuePose::STANDING;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_POSE, self::poseToId($this->pose));
	}

	public function getPose() : CopperGolemStatuePose{
		return $this->pose;
	}

	public function setPose(CopperGolemStatuePose $pose) : void{
		$this->pose = $pose;
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_POSE, self::poseToId($this->pose));
	}

	private static function poseToId(CopperGolemStatuePose $pose) : int{
		return match($pose){
			CopperGolemStatuePose::STANDING => 0,
			CopperGolemStatuePose::SITTING => 1,
			CopperGolemStatuePose::RUNNING => 2,
			CopperGolemStatuePose::STAR => 3,
		};
	}

	private static function poseFromId(int $id) : ?CopperGolemStatuePose{
		return match($id){
			0 => CopperGolemStatuePose::STANDING,
			1 => CopperGolemStatuePose::SITTING,
			2 => CopperGolemStatuePose::RUNNING,
			3 => CopperGolemStatuePose::STAR,
			default => null,
		};
	}
}
