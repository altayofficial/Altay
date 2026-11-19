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
use function is_string;
use function mb_check_encoding;
use function mb_strlen;

/**
 * @internal
 */
final class TextField extends InputElement{

	/**
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<string>                          $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 */
	public function __construct(
		string|Translatable|Observable $label,
		private Observable $value,
		private int $maxLength,
		string|Translatable|Observable|null $description,
		bool|Observable $visible,
		bool|Observable $disabled
	){
		if(!is_string($value->get())){
			throw new \InvalidArgumentException("TextField needs a string Observable");
		}
		if($maxLength < 1){
			throw new \InvalidArgumentException("TextField maxLength must be at least 1, got $maxLength");
		}
		parent::__construct($label, $description, $visible, $disabled);
	}

	protected function getVisibleKey() : string{
		return "textfield_visible";
	}

	public function compile(ScreenBinder $binder, string $path) : array{
		$data = $this->compileFrame($binder, $path);
		$data["text"] = $binder->value($this->value, "$path.text", function(string|float|bool $raw) : ?string{
			if(!is_string($raw) || !mb_check_encoding($raw, 'UTF-8') || mb_strlen($raw, 'UTF-8') > $this->maxLength || !$this->isInteractive()){ //godly check lmao, %100'd my brain here
				return null;
			}
			return $raw;
		});
		return $data;
	}
}
