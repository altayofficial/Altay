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
use pocketmine\lang\Translatable;
use function intdiv;
use function is_finite;
use function is_float;
use function is_int;
use function max;
use function min;
use function round;

/**
 * @internal
 */
final class Slider extends InputElement{

	/**
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<int>                             $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 */
	public function __construct(
		string|Translatable|Observable $label,
		private Observable $value,
		private int $min,
		private int $max,
		private int $step,
		string|Translatable|Observable|null $description,
		bool|Observable $visible,
		bool|Observable $disabled
	){
		if(!is_int($value->get())){
			throw new \InvalidArgumentException("Slider needs an int Observable");
		}
		if($min >= $max){
			throw new \InvalidArgumentException("Slider min ($min) must be less than max ($max)");
		}
		if($step < 1){
			throw new \InvalidArgumentException("Slider step must be at least 1, got $step");
		}
		parent::__construct($label, $description, $visible, $disabled);
	}

	protected function getVisibleKey() : string{
		return "slider_visible";
	}

	public function compile(ScreenBinder $binder, string $path) : array{
		$data = $this->compileFrame($binder, $path);
		$data["minValue"] = $this->min;
		$data["maxValue"] = $this->max;
		$data["step"] = $this->step;
		$data["value"] = $binder->value($this->value, "$path.value", function(string|float|bool $raw) : ?int{
			if(!is_float($raw) || !is_finite($raw) || !$this->isInteractive()){
				return null;
			}
			return $this->snap($raw);
		});
		return $data;
	}

	/**
	 * Clamps to the range and rounds to the nearest step counted from min, the same grid the client draws.
	 */
	private function snap(float $raw) : int{
		$clamped = (int) round(max($this->min, min($this->max, $raw)));
		$steps = intdiv($clamped - $this->min + intdiv($this->step, 2), $this->step);
		$snapped = $this->min + $steps * $this->step;
		//ok so rounding up can overshoot when (max - min) isn't a multiple of step
		return $snapped > $this->max ? $snapped - $this->step : $snapped;
	}
}
