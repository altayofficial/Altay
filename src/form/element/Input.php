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
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use function get_debug_type;
use function is_string;

/**
 * Text field, its value is the typed string.
 */
final class Input implements InputElement{
	public const TYPE = "input";

	/**
	 * @param string $default prefilled value, kept as a plain string since the player edits it
	 */
	public function __construct(
		private string $name,
		private Translatable|string $text,
		private Translatable|string $placeholder = "",
		private string $default = "",
		private Translatable|string|null $tooltip = null
	){}

	public function getName() : string{ return $this->name; }

	public function getText() : Translatable|string{ return $this->text; }

	public function getPlaceholder() : Translatable|string{ return $this->placeholder; }

	public function getDefault() : string{ return $this->default; }

	public function getTooltip() : Translatable|string|null{ return $this->tooltip; }

	public function validateValue(mixed $value) : string{
		if(!is_string($value)){
			throw new FormValidationException("Input \"$this->name\" expects string, got " . get_debug_type($value));
		}
		return $value;
	}

	public function serializeForLanguage(Language $language) : array{
		$result = [
			"type" => self::TYPE,
			"text" => $this->text instanceof Translatable ? $language->translate($this->text) : $this->text,
			"placeholder" => $this->placeholder instanceof Translatable ? $language->translate($this->placeholder) : $this->placeholder,
			"default" => $this->default,
		];
		if($this->tooltip !== null){
			$result["tooltip"] = $this->tooltip instanceof Translatable ? $language->translate($this->tooltip) : $this->tooltip;
		}
		return $result;
	}
}
