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

namespace pocketmine\form\datadriven;

use pocketmine\form\datadriven\element\Button;
use pocketmine\form\datadriven\element\CloseButton;
use pocketmine\form\datadriven\element\Dropdown;
use pocketmine\form\datadriven\element\Element;
use pocketmine\form\datadriven\element\Separator;
use pocketmine\form\datadriven\element\Slider;
use pocketmine\form\datadriven\element\Text;
use pocketmine\form\datadriven\element\TextField;
use pocketmine\form\datadriven\element\Toggle;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use function count;

/**
 * A screen made of a list of elements. Add elements in the order they should appear, then show() it.
 * Input elements are backed by Observables: read them whenever you like, and set them to update every viewer.
 *
 * Text arguments accept a plain string, a Translatable (translated into each player's language when shown) or a
 * string Observable for text that changes while the screen is open.
 */
final class CustomScreen extends DataDrivenScreen{
	/**
	 * @var Element[]
	 * @phpstan-var list<Element>
	 */
	private array $elements = [];
	private ?CloseButton $closeButton = null;

	/**
	 * @phpstan-param string|Translatable|Observable<string> $title
	 */
	public function __construct(
		private string|Translatable|Observable $title
	){}

	/**
	 * @phpstan-param string|Translatable|Observable<string> $text
	 * @phpstan-param bool|Observable<bool>                  $visible
	 * @return $this
	 */
	public function header(string|Translatable|Observable $text, bool|Observable $visible = true) : self{
		return $this->add(new Text(Text::HEADER, $text, $visible));
	}

	/**
	 * @phpstan-param string|Translatable|Observable<string> $text
	 * @phpstan-param bool|Observable<bool>                  $visible
	 * @return $this
	 */
	public function label(string|Translatable|Observable $text, bool|Observable $visible = true) : self{
		return $this->add(new Text(Text::LABEL, $text, $visible));
	}

	/**
	 * @phpstan-param bool|Observable<bool> $visible
	 * @return $this
	 */
	public function divider(bool|Observable $visible = true) : self{
		return $this->add(new Separator(Separator::DIVIDER, $visible));
	}

	/**
	 * @phpstan-param bool|Observable<bool> $visible
	 * @return $this
	 */
	public function spacer(bool|Observable $visible = true) : self{
		return $this->add(new Separator(Separator::SPACER, $visible));
	}

	/**
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<bool>                            $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 * @return $this
	 */
	public function toggle(string|Translatable|Observable $label, Observable $value, string|Translatable|Observable|null $description = null, bool|Observable $visible = true, bool|Observable $disabled = false) : self{
		return $this->add(new Toggle($label, $value, $description, $visible, $disabled));
	}

	/**
	 * Whole number slider. Whatever the client sends is clamped to min/max and snapped to step; if that changes
	 * the value, the corrected one is sent back.
	 *
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<int>                             $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 * @return $this
	 */
	public function slider(string|Translatable|Observable $label, Observable $value, int $min, int $max, int $step = 1, string|Translatable|Observable|null $description = null, bool|Observable $visible = true, bool|Observable $disabled = false) : self{
		return $this->add(new Slider($label, $value, $min, $max, $step, $description, $visible, $disabled));
	}

	/**
	 * $value holds the index of the selected option.
	 *
	 * @param string[]|Translatable[] $options
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<int>                             $value
	 * @phpstan-param list<string|Translatable>                   $options
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 * @return $this
	 */
	public function dropdown(string|Translatable|Observable $label, Observable $value, array $options, string|Translatable|Observable|null $description = null, bool|Observable $visible = true, bool|Observable $disabled = false) : self{
		return $this->add(new Dropdown($label, $value, $options, $description, $visible, $disabled));
	}

	/**
	 * Text the client sends past $maxLength characters is refused and the field is reset to the last good value.
	 *
	 * @phpstan-param string|Translatable|Observable<string>      $label
	 * @phpstan-param Observable<string>                          $value
	 * @phpstan-param string|Translatable|Observable<string>|null $description
	 * @phpstan-param bool|Observable<bool>                       $visible
	 * @phpstan-param bool|Observable<bool>                       $disabled
	 * @return $this
	 */
	public function textField(string|Translatable|Observable $label, Observable $value, int $maxLength = 256, string|Translatable|Observable|null $description = null, bool|Observable $visible = true, bool|Observable $disabled = false) : self{
		return $this->add(new TextField($label, $value, $maxLength, $description, $visible, $disabled));
	}

	/**
	 * The screen stays open after a click; close it from the callback if you want to.
	 * Clicks on hidden or disabled buttons are ignored.
	 *
	 * @phpstan-param string|Translatable|Observable<string>                               $label
	 * @phpstan-param \Closure(Player $player, DataDrivenScreenSession $session) : void    $onClick
	 * @phpstan-param string|Translatable|Observable<string>|null                          $tooltip
	 * @phpstan-param bool|Observable<bool>                                                $visible
	 * @phpstan-param bool|Observable<bool>                                                $disabled
	 * @return $this
	 */
	public function button(string|Translatable|Observable $label, \Closure $onClick, string|Translatable|Observable|null $tooltip = null, bool|Observable $visible = true, bool|Observable $disabled = false) : self{
		Utils::validateCallableSignature(function(Player $player, DataDrivenScreenSession $session) : void{}, $onClick);
		return $this->add(new Button($label, $onClick, $tooltip, $visible, $disabled));
	}

	/**
	 * Adds the button that closes the screen. Pressing it closes with ClientCanceled. Only one per screen.
	 *
	 * @phpstan-param string|Translatable|Observable<string> $label
	 * @phpstan-param bool|Observable<bool>                  $visible
	 * @return $this
	 */
	public function closeButton(string|Translatable|Observable $label = "Close", bool|Observable $visible = true) : self{
		$this->checkNotLocked();
		if($this->closeButton !== null){
			throw new \LogicException("A screen can only have one close button");
		}
		$this->closeButton = new CloseButton($label, $visible);
		return $this;
	}

	/**
	 * @return $this
	 */
	private function add(Element $element) : self{
		$this->checkNotLocked();
		$this->elements[] = $element;
		return $this;
	}

	public function getScreenId() : string{
		return "minecraft:custom_form";
	}

	public function getPropertyName() : string{
		return "custom_form_data";
	}

	public function compile(ScreenBinder $binder) : array{
		$layout = [];
		foreach($this->elements as $index => $element){
			$layout[(string) $index] = $element->compile($binder, "layout[$index]");
		}
		//hmm, the client wants arrays as objects with an explicit length, not as real lists
		$layout["length"] = count($this->elements);

		$data = [
			"title" => $binder->text($this->title, "title"),
			"layout" => $layout,
		];
		if($this->closeButton !== null){
			$data["closeButton"] = $this->closeButton->compile($binder, "closeButton");
		}
		return $data;
	}
}
