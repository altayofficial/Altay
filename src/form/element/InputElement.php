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

namespace pocketmine\form\element;

use pocketmine\form\FormValidationException;

/**
 * A CustomForm element the player can change, so it shows up in CustomFormResponse under its name.
 */
interface InputElement extends CustomFormElement{

	public function getName() : string;

	/**
	 * Checks the raw value the client sent for this element and turns it into what CustomFormResponse holds.
	 *
	 * @throws FormValidationException
	 */
	public function validateValue(mixed $value) : bool|int|float|string;
}
