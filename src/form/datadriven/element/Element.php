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

namespace pocketmine\form\datadriven\element;

use pocketmine\form\datadriven\Observable;
use pocketmine\form\datadriven\ScreenBinder;

/**
 * @internal
 * One entry of a CustomScreen. Plugins never see these, they go through CustomScreen's builder methods.
 */
abstract class Element{

	/**
	 * Builds this element's part of the screen data tree, binding any Observables on the way.
	 *
	 * @return mixed[]
	 * @phpstan-return array<string, mixed>
	 */
	abstract public function compile(ScreenBinder $binder, string $path) : array;

	/**
	 * @phpstan-param bool|Observable<bool> $flag
	 */
	protected static function isSet(bool|Observable $flag) : bool{
		return $flag instanceof Observable ? $flag->get() : $flag;
	}
}
