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

namespace pocketmine\form;

use pocketmine\form\element\Button;
use pocketmine\form\element\SimpleFormElement;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;
use function array_map;
use function array_values;
use function count;
use function get_debug_type;
use function is_int;

/**
 * A list of buttons, optionally split up with headers, labels and dividers.
 * The submit handler gets the index of the pressed button, counting buttons only.
 *
 * ```
 * $player->sendForm(new SimpleForm(
 *     "Warps",
 *     "Where to?",
 *     [new Header("Hubs"), new Button("Spawn", FormIcon::path("textures/items/bed_red")), new Divider(), new Button("Arena")],
 *     function(Player $player, int $selected) : void{ ... }
 * ));
 * ```
 */
final class SimpleForm implements TranslatableForm{
	public const TYPE = "form";

	/** @phpstan-var list<SimpleFormElement> */
	private array $elements;
	/** @phpstan-var list<Button> */
	private array $buttons = [];
	/** @phpstan-var \Closure(Player, int) : void */
	private \Closure $onSubmit;
	/** @phpstan-var (\Closure(Player) : void)|null */
	private ?\Closure $onClose;

	/**
	 * @param SimpleFormElement[] $elements
	 * @phpstan-param \Closure(Player $player, int $selectedButton) : void $onSubmit
	 * @phpstan-param (\Closure(Player $player) : void)|null $onClose
	 */
	public function __construct(
		private Translatable|string $title,
		private Translatable|string $content,
		array $elements,
		\Closure $onSubmit,
		?\Closure $onClose = null
	){
		Utils::validateCallableSignature(function(Player $player, int $selectedButton) : void{}, $onSubmit);
		$this->onSubmit = $onSubmit;
		if($onClose !== null){
			Utils::validateCallableSignature(function(Player $player) : void{}, $onClose);
		}
		$this->onClose = $onClose;
		$this->elements = array_values($elements);
		foreach($this->elements as $element){
			if($element instanceof Button){
				$this->buttons[] = $element;
			}
		}
	}

	public function getTitle() : Translatable|string{ return $this->title; }

	public function getContent() : Translatable|string{ return $this->content; }

	/**
	 * @return SimpleFormElement[]
	 * @phpstan-return list<SimpleFormElement>
	 */
	public function getElements() : array{ return $this->elements; }

	/**
	 * @return Button[]
	 * @phpstan-return list<Button>
	 */
	public function getButtons() : array{ return $this->buttons; }

	/**
	 * Checks a raw response from the client and returns the pressed button's index, without running any handlers.
	 *
	 * @throws FormValidationException
	 */
	public function parseResponse(mixed $data) : int{
		// yeah, the client counts buttons only, headers/labels/dividers in between don't shift the index
		if(!is_int($data)){
			throw new FormValidationException("Expected int for simple form response, got " . get_debug_type($data));
		}
		if(!isset($this->buttons[$data])){
			throw new FormValidationException("Button index $data is out of range, form has " . count($this->buttons) . " buttons");
		}
		return $data;
	}

	/**
	 * A null response means the player closed the form, or was busy on a loading screen.
	 */
	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			if($this->onClose !== null){
				($this->onClose)($player);
			}
			return;
		}
		($this->onSubmit)($player, $this->parseResponse($data));
	}

	public function serializeForLanguage(Language $language) : array{
		return [
			"type" => self::TYPE,
			"title" => $this->title instanceof Translatable ? $language->translate($this->title) : $this->title,
			"content" => $this->content instanceof Translatable ? $language->translate($this->content) : $this->content,
			"elements" => array_map(fn(SimpleFormElement $element) => $element->serializeForLanguage($language), $this->elements),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize() : array{
		// hmm, no player here, so the best we can do is the server language
		return $this->serializeForLanguage(Server::getInstance()->getLanguage());
	}
}
