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
use function array_is_list;
use function count;
use function floor;
use function is_finite;
use function is_float;
use function is_int;

/**
 * @internal
 */
final class Dropdown extends InputElement{

	/**
	 * @param string[]|Translatable[] $options
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<int>                             $value
	 * @phpstan-param list<string|Translatable>                   $options
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 */
	public function __construct(
		string|Translatable|Observable $label,
		private Observable $value,
		private array $options,
		string|Translatable|Observable|null $description,
		bool|Observable $visible,
		bool|Observable $disabled
	){
		$selected = $value->get();
		if(!is_int($selected)){
			throw new \InvalidArgumentException("Dropdown needs an int Observable holding the selected index");
		}
		if($options === [] || !array_is_list($options)){
			throw new \InvalidArgumentException("Dropdown options must be a non-empty list");
		}
		if($selected < 0 || $selected >= count($options)){
			throw new \InvalidArgumentException("Selected index $selected is out of range for " . count($options) . " options");
		}
		parent::__construct($label, $description, $visible, $disabled);
	}

	protected function getVisibleKey() : string{
		return "dropdown_visible";
	}

	public function compile(ScreenBinder $binder, string $path) : array{
		$data = $this->compileFrame($binder, $path);
		$items = [];
		foreach($this->options as $index => $option){
			$items[(string) $index] = [
				"label" => $binder->translate($option),
				"description" => "",
				"value" => $index,
			];
		}
		$items["length"] = count($this->options);
		$data["items"] = $items;
		$data["value"] = $binder->value($this->value, "$path.value", function(string|float|bool $raw) : ?int{
			if(!is_float($raw) || !is_finite($raw) || floor($raw) !== $raw || !$this->isInteractive()){
				return null;
			}
			$index = (int) $raw;
			return $index >= 0 && $index < count($this->options) ? $index : null;
		});
		return $data;
	}
}
