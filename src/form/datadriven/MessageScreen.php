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

use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

/**
 * A title, a body text and two buttons. Pressing either button closes the screen (reason Responded) and then
 * runs that button's callback, so the callback is free to open another screen.
 */
final class MessageScreen extends DataDrivenScreen{
	/** @phpstan-var string|Translatable|Observable<string>|null */
	private string|Translatable|Observable|null $button1Label = null;
	/** @phpstan-var (\Closure(Player, DataDrivenScreenSession) : void)|null */
	private ?\Closure $button1Click = null;
	/** @phpstan-var string|Translatable|Observable<string>|null */
	private string|Translatable|Observable|null $button2Label = null;
	/** @phpstan-var (\Closure(Player, DataDrivenScreenSession) : void)|null */
	private ?\Closure $button2Click = null;

	/**
	 * @phpstan-param string|Translatable|Observable<string> $title
	 * @phpstan-param string|Translatable|Observable<string> $body
	 */
	public function __construct(
		private string|Translatable|Observable $title,
		private string|Translatable|Observable $body
	){}

	/**
	 * @phpstan-param string|Translatable|Observable<string>                            $label
	 * @phpstan-param \Closure(Player $player, DataDrivenScreenSession $session) : void $onClick
	 * @return $this
	 */
	public function button1(string|Translatable|Observable $label, \Closure $onClick) : self{
		$this->checkNotLocked();
		Utils::validateCallableSignature(function(Player $player, DataDrivenScreenSession $session) : void{}, $onClick);
		$this->button1Label = $label;
		$this->button1Click = $onClick;
		return $this;
	}

	/**
	 * @phpstan-param string|Translatable|Observable<string>                            $label
	 * @phpstan-param \Closure(Player $player, DataDrivenScreenSession $session) : void $onClick
	 * @return $this
	 */
	public function button2(string|Translatable|Observable $label, \Closure $onClick) : self{
		$this->checkNotLocked();
		Utils::validateCallableSignature(function(Player $player, DataDrivenScreenSession $session) : void{}, $onClick);
		$this->button2Label = $label;
		$this->button2Click = $onClick;
		return $this;
	}

	protected function validate() : void{
		if($this->button1Click === null || $this->button2Click === null){
			throw new \LogicException("MessageScreen needs both button1() and button2() set before it can be shown");
		}
	}

	public function getScreenId() : string{
		return "minecraft:message_box";
	}

	public function getPropertyName() : string{
		return "message_box_data";
	}

	public function compile(ScreenBinder $binder) : array{
		if($this->button1Label === null || $this->button1Click === null || $this->button2Label === null || $this->button2Click === null){
			throw new \LogicException("MessageScreen needs both buttons set");
		}
		return [
			"title" => $binder->text($this->title, "title"),
			"body" => $binder->text($this->body, "body"),
			//yeah, vanilla message_box.json hides the buttons unless one of these layout flags is set
			"is_vertical" => true,
			"is_horizontal" => false,
			"button1" => $this->compileButton($binder, "button1", $this->button1Label, $this->button1Click),
			"button2" => $this->compileButton($binder, "button2", $this->button2Label, $this->button2Click),
		];
	}

	/**
	 * @phpstan-param string|Translatable|Observable<string>              $label
	 * @phpstan-param \Closure(Player, DataDrivenScreenSession) : void    $onClick
	 * @return mixed[]
	 * @phpstan-return array<string, mixed>
	 */
	private function compileButton(ScreenBinder $binder, string $path, string|Translatable|Observable $label, \Closure $onClick) : array{
		return [
			"label" => $binder->text($label, "$path.label"),
			"onClick" => $binder->action("$path.onClick", function() use ($binder, $onClick) : void{
				$session = $binder->getSession();
				if(!$session->isOpen()){
					return;
				}
				$session->finish(DataDrivenScreenCloseReason::Responded, true);
				$onClick($binder->getPlayer(), $session);
			}),
		];
	}
}
