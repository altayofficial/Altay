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

namespace pocketmine\data\bedrock;

use pocketmine\block\utils\MushroomBlockType;
use pocketmine\data\bedrock\block\convert\property\ValueMappings;
use pocketmine\utils\SingletonTrait;

/**
 * @deprecated
 */
final class MushroomBlockTypeIdMap{
	use SingletonTrait;
	/** @phpstan-use IntSaveIdMapTrait<MushroomBlockType> */
	use IntSaveIdMapTrait;

	public function __construct(){
		$newMapping = ValueMappings::getInstance()->mushroomBlockType;
		foreach(MushroomBlockType::cases() as $case){
			$this->register($newMapping->valueToRaw($case), $case);
		}
	}
}
