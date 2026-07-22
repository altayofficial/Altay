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

namespace pocketmine\entity\effect;

use pocketmine\color\Color;
use pocketmine\lang\Translatable;

abstract class InstantEffect extends Effect{

	public function __construct(Translatable|string $name, Color $color, bool $bad = false, bool $hasBubbles = true){
		parent::__construct($name, $color, $bad, 1, $hasBubbles);
	}

	public function getApplyInterval(EffectInstance $instance) : int{
		return 1; //If forced to last longer than 1 tick, these apply every tick.
	}
}
