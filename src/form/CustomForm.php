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

use pocketmine\form\element\CustomFormElement;
use pocketmine\form\element\InputElement;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;
use function array_is_list;
use function array_map;
use function array_values;
use function count;
use function get_debug_type;
use function is_array;

/**
 * Form made of input elements. Every input has a name, which is how you read its value back from the response.
 * This is also the form type the client shows in its server settings screen.
 *
 * ```
 * $player->sendForm(new CustomForm(
 *     "Settings",
 *     [
 *         new Header("Gameplay"),
 *         new Toggle("pvp", "PvP", default: true, tooltip: "Lets other players hit you"),
 *         new Slider("fov", "FOV", 30, 110, 5, 70),
 *     ],
 *     function(Player $player, CustomFormResponse $response) : void{
 *         $pvp = $response->getBool("pvp");
 *     },
 *     submitText: "Save"
 * ));
 * ```
 */
final class CustomForm implements TranslatableForm{
	/** @phpstan-var list<CustomFormElement> */
	private array $elements;
	/** @phpstan-var \Closure(Player, CustomFormResponse) : void */
	private \Closure $onSubmit;
	/** @phpstan-var (\Closure(Player) : void)|null */
	private ?\Closure $onClose;

	/**
	 * @param CustomFormElement[] $elements
	 * @param FormIcon|null       $icon     only shown when the form is sent as server settings
	 * @phpstan-param \Closure(Player $player, CustomFormResponse $response) : void $onSubmit
	 * @phpstan-param (\Closure(Player $player) : void)|null $onClose
	 *
	 * @throws \InvalidArgumentException if two inputs share a name
	 */
	public function __construct(
		private Translatable|string $title,
		array $elements,
		\Closure $onSubmit,
		?\Closure $onClose = null,
		private Translatable|string|null $submitText = null,
		private ?FormIcon $icon = null
	){
		Utils::validateCallableSignature(function(Player $player, CustomFormResponse $response) : void{}, $onSubmit);
		$this->onSubmit = $onSubmit;
		if($onClose !== null){
			Utils::validateCallableSignature(function(Player $player) : void{}, $onClose);
		}
		$this->onClose = $onClose;

		$this->elements = array_values($elements);
		$names = [];
		foreach($this->elements as $element){
			if($element instanceof InputElement){
				$name = $element->getName();
				if(isset($names[$name])){
					throw new \InvalidArgumentException("Two inputs are named \"$name\", names must be unique within a form");
				}
				$names[$name] = true;
			}
		}
	}

	public function getTitle() : Translatable|string{ return $this->title; }

	/**
	 * @return CustomFormElement[]
	 * @phpstan-return list<CustomFormElement>
	 */
	public function getElements() : array{ return $this->elements; }

	/**
	 * Text of the submit button, null leaves the client's default.
	 */
	public function getSubmitText() : Translatable|string|null{ return $this->submitText; }

	public function getIcon() : ?FormIcon{ return $this->icon; }

	/**
	 * Checks a raw response from the client and turns it into named values, without running any handlers.
	 * The client sends one value per element in order, with null for headers, labels and dividers.
	 *
	 * @throws FormValidationException
	 */
	public function parseResponse(mixed $data) : CustomFormResponse{
		if(!is_array($data) || !array_is_list($data)){
			throw new FormValidationException("Expected list for custom form response, got " . get_debug_type($data));
		}
		if(count($data) !== count($this->elements)){
			throw new FormValidationException("Expected " . count($this->elements) . " values in custom form response, got " . count($data));
		}

		$values = [];
		foreach($this->elements as $index => $element){
			$value = $data[$index];
			if($element instanceof InputElement){
				$values[$element->getName()] = $element->validateValue($value);
			}elseif($value !== null){
				throw new FormValidationException("Expected null for non input element at index $index, got " . get_debug_type($value));
			}
		}
		return new CustomFormResponse($values);
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
		$result = [
			"type" => FormType::CUSTOM_FORM->value,
			"title" => $this->title instanceof Translatable ? $language->translate($this->title) : $this->title,
			"content" => array_map(fn(CustomFormElement $element) => $element->serializeForLanguage($language), $this->elements),
		];
		if($this->submitText !== null){
			$result["submit"] = $this->submitText instanceof Translatable ? $language->translate($this->submitText) : $this->submitText;
		}
		if($this->icon !== null){
			$result["icon"] = $this->icon;
		}
		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize() : array{
		// hmm, no player here, so the best we can do is the server language
		return $this->serializeForLanguage(Server::getInstance()->getLanguage());
	}
}
