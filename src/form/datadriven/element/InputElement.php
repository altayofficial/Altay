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
 * Shared frame for the elements the player can change: a label, a description line, and visible/disabled flags.
 */
abstract class InputElement extends Element{

	/**
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 */
	public function __construct(
		private string|Translatable|Observable $label,
		private string|Translatable|Observable|null $description,
		private bool|Observable $visible,
		private bool|Observable $disabled
	){}

	/** Key the client uses to tell which kind of element this is, e.g. "toggle_visible". */
	abstract protected function getVisibleKey() : string;

	/**
	 * Writes from a hidden or disabled element mean the client is out of sync or lying, either way we drop them.
	 */
	protected function isInteractive() : bool{
		return self::isSet($this->visible) && !self::isSet($this->disabled);
	}

	/**
	 * @return mixed[]
	 * @phpstan-return array<string, mixed>
	 */
	protected function compileFrame(ScreenBinder $binder, string $path) : array{
		$visibleKey = $this->getVisibleKey();
		$visible = $binder->flag($this->visible, "$path.visible", "$path.$visibleKey");
		return [
			"label" => $binder->text($this->label, "$path.label"),
			"description" => $binder->text($this->description ?? "", "$path.description"),
			"visible" => $visible,
			$visibleKey => $visible,
			"disabled" => $binder->flag($this->disabled, "$path.disabled"),
		];
	}
}
