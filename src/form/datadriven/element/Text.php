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

/**
 * @internal
 * Headers and labels. They only differ in which "*_visible" key the client looks for.
 */
final class Text extends Element{
	public const HEADER = "header_visible";
	public const LABEL = "label_visible";

	/**
	 * @phpstan-param string|Translatable|Observable<string> $text
	 * @phpstan-param bool|Observable<bool>                  $visible
	 */
	public function __construct(
		private string $kind,
		private string|Translatable|Observable $text,
		private bool|Observable $visible
	){}

	public function compile(ScreenBinder $binder, string $path) : array{
		$visible = $binder->flag($this->visible, "$path.visible", "$path.$this->kind");
		return [
			"text" => $binder->text($this->text, "$path.text"),
			"visible" => $visible,
			$this->kind => $visible,
		];
	}
}
