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

namespace pocketmine\item;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;

class Pufferfish extends Food{

	public function getFoodRestore() : int{
		return 1;
	}

	public function getSaturationRestore() : float{
		return 0.2;
	}

	public function getAdditionalEffects() : array{
		return [
			new EffectInstance(VanillaEffects::HUNGER(), 300, 2),
			new EffectInstance(VanillaEffects::POISON(), 1200, 3),
			new EffectInstance(VanillaEffects::NAUSEA(), 300, 1)
		];
	}
}
