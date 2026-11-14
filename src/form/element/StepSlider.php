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
use function array_map;
use function array_values;
use function count;
use function get_debug_type;
use function is_int;

/**
 * Slider snapping between a fixed list of labelled steps, its value is the index of the picked step as an int.
 */
final class StepSlider implements InputElement{
	/** @phpstan-var list<Translatable|string> */
	private array $steps;

	/**
	 * @param Translatable[]|string[] $steps
	 * @phpstan-param array<Translatable|string> $steps
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		private string $name,
		private Translatable|string $text,
		array $steps,
		private int $default = 0
	){
		$this->steps = array_values($steps);
		if(count($this->steps) === 0){
			throw new \InvalidArgumentException("StepSlider needs at least one step");
		}
		if(!isset($this->steps[$default])){
			throw new \InvalidArgumentException("Default index $default is out of range for " . count($this->steps) . " steps");
		}
	}

	public function getName() : string{ return $this->name; }

	public function getText() : Translatable|string{ return $this->text; }

	/**
	 * @return Translatable[]|string[]
	 * @phpstan-return list<Translatable|string>
	 */
	public function getSteps() : array{ return $this->steps; }

	/**
	 * The step at the given index, handy for turning a response value back into its label.
	 */
	public function getStep(int $index) : Translatable|string|null{
		return $this->steps[$index] ?? null;
	}

	public function getDefault() : int{ return $this->default; }

	public function validateValue(mixed $value) : int{
		if(!is_int($value)){
			throw new FormValidationException("StepSlider \"$this->name\" expects int, got " . get_debug_type($value));
		}
		if(!isset($this->steps[$value])){
			throw new FormValidationException("StepSlider \"$this->name\" index $value is out of range for " . count($this->steps) . " steps");
		}
		return $value;
	}

	public function serializeForLanguage(Language $language) : array{
		$result = [
			"type" => ElementType::STEP_SLIDER->value,
			"text" => $this->text instanceof Translatable ? $language->translate($this->text) : $this->text,
			"steps" => array_map(fn(Translatable|string $step) => $step instanceof Translatable ? $language->translate($step) : $step, $this->steps),
			"default" => $this->default,
		];
		return $result;
	}
}
