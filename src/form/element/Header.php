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

use pocketmine\lang\Language;
use pocketmine\lang\Translatable;

/**
 * Bold section title.
 */
final class Header implements SimpleFormElement, CustomFormElement{
	public function __construct(
		private Translatable|string $text
	){}

	public function getText() : Translatable|string{ return $this->text; }

	public function serializeForLanguage(Language $language) : array{
		return [
			"type" => ElementType::HEADER->value,
			"text" => $this->text instanceof Translatable ? $language->translate($this->text) : $this->text,
		];
	}
}
