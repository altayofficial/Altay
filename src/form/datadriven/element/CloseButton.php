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

use pocketmine\form\datadriven\DataDrivenScreenCloseReason;
use pocketmine\form\datadriven\Observable;
use pocketmine\form\datadriven\ScreenBinder;
use pocketmine\lang\Translatable;

/**
 * @internal
 * Lives at the root of the custom form tree, not inside the layout.
 */
final class CloseButton extends Element{

	/**
	 * @phpstan-param string|Translatable|Observable<string> $label
	 * @phpstan-param bool|Observable<bool>                  $visible
	 */
	public function __construct(
		private string|Translatable|Observable $label,
		private bool|Observable $visible
	){}

	public function compile(ScreenBinder $binder, string $path) : array{
		$visible = $binder->flag($this->visible, "$path.visible", "$path.button_visible");
		return [
			"label" => $binder->text($this->label, "$path.label"),
			"visible" => $visible,
			"button_visible" => $visible,
			"onClick" => $binder->action("$path.onClick", function() use ($binder) : void{
				if(self::isSet($this->visible)){
					$binder->getSession()->finish(DataDrivenScreenCloseReason::ClientCanceled, true);
				}
			}),
		];
	}
}
