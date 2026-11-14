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
use function is_float;
use function is_int;

/**
 * Numeric slider between min and max, its value is a float.
 */
final class Slider implements InputElement{
	public const TYPE = "slider";

	private float $default;

	/**
	 * @param float|null $default starts at min when null
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		private string $name,
		private Translatable|string $text,
		private float $min,
		private float $max,
		private float $step = 1.0,
		?float $default = null,
		private Translatable|string|null $tooltip = null
	){
		if($min > $max){
			throw new \InvalidArgumentException("Slider min ($min) must not be greater than max ($max)");
		}
		if($step <= 0){
			throw new \InvalidArgumentException("Slider step must be positive, got $step");
		}
		$default ??= $min;
		if($default < $min || $default > $max){
			throw new \InvalidArgumentException("Slider default $default is outside of [$min, $max]");
		}
		$this->default = $default;
	}

	public function getName() : string{ return $this->name; }

	public function getText() : Translatable|string{ return $this->text; }

	public function getMin() : float{ return $this->min; }

	public function getMax() : float{ return $this->max; }

	public function getStep() : float{ return $this->step; }

	public function getDefault() : float{ return $this->default; }

	public function getTooltip() : Translatable|string|null{ return $this->tooltip; }

	public function validateValue(mixed $value) : float{
		// json_decode hands us an int whenever the value has no fractional part
		if(!is_int($value) && !is_float($value)){
			throw new FormValidationException("Slider \"$this->name\" expects a number, got " . get_debug_type($value));
		}
		$value = (float) $value;
		if($value < $this->min || $value > $this->max){
			throw new FormValidationException("Slider \"$this->name\" value $value is outside of [$this->min, $this->max]");
		}
		return $value;
	}

	public function serializeForLanguage(Language $language) : array{
		$result = [
			"type" => self::TYPE,
			"text" => $this->text instanceof Translatable ? $language->translate($this->text) : $this->text,
			"min" => $this->min,
			"max" => $this->max,
			"step" => $this->step,
			"default" => $this->default,
		];
		if($this->tooltip !== null){
			$result["tooltip"] = $this->tooltip instanceof Translatable ? $language->translate($this->tooltip) : $this->tooltip;
		}
		return $result;
	}
}
