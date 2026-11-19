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
use function is_bool;

/**
 * @internal
 */
final class Toggle extends InputElement{

	/**
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<bool>                            $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 */
	public function __construct(
		string|Translatable|Observable $label,
		private Observable $value,
		string|Translatable|Observable|null $description,
		bool|Observable $visible,
		bool|Observable $disabled
	){
		if(!is_bool($value->get())){
			throw new \InvalidArgumentException("Toggle needs a bool Observable");
		}
		parent::__construct($label, $description, $visible, $disabled);
	}

	protected function getVisibleKey() : string{
		return "toggle_visible";
	}

	public function compile(ScreenBinder $binder, string $path) : array{
		$data = $this->compileFrame($binder, $path);
		$data["toggled"] = $binder->value($this->value, "$path.toggled",
			fn(string|float|bool $raw) : ?bool => is_bool($raw) && $this->isInteractive() ? $raw : null
		);
		return $data;
	}
}
