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

use pocketmine\form\FormIcon;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;

final class Button implements SimpleFormElement{
	public function __construct(
		private Translatable|string $text,
		private ?FormIcon $icon = null
	){}

	public function getText() : Translatable|string{ return $this->text; }

	public function getIcon() : ?FormIcon{ return $this->icon; }

	public function serializeForLanguage(Language $language) : array{
		$result = [
			"type" => ElementType::BUTTON->value,
			"text" => $this->text instanceof Translatable ? $language->translate($this->text) : $this->text,
		];
		if($this->icon !== null){
			$result["image"] = $this->icon;
		}
		return $result;
	}
}
