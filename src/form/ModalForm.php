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

use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;
use function get_debug_type;
use function is_bool;

/**
 * Two button dialog. The submit handler gets true for the confirm button and false for the cancel one.
 */
final class ModalForm implements TranslatableForm{
	public const TYPE = "modal";

	/** @phpstan-var \Closure(Player, bool) : void */
	private \Closure $onSubmit;
	/** @phpstan-var (\Closure(Player) : void)|null */
	private ?\Closure $onClose;

	/**
	 * @phpstan-param \Closure(Player $player, bool $confirmed) : void $onSubmit
	 * @phpstan-param (\Closure(Player $player) : void)|null $onClose
	 */
	public function __construct(
		private Translatable|string $title,
		private Translatable|string $content,
		private Translatable|string $confirmText,
		private Translatable|string $cancelText,
		\Closure $onSubmit,
		?\Closure $onClose = null
	){
		Utils::validateCallableSignature(function(Player $player, bool $confirmed) : void{}, $onSubmit);
		$this->onSubmit = $onSubmit;
		if($onClose !== null){
			Utils::validateCallableSignature(function(Player $player) : void{}, $onClose);
		}
		$this->onClose = $onClose;
	}

	public function getTitle() : Translatable|string{ return $this->title; }

	public function getContent() : Translatable|string{ return $this->content; }

	public function getConfirmText() : Translatable|string{ return $this->confirmText; }

	public function getCancelText() : Translatable|string{ return $this->cancelText; }

	/**
	 * Checks a raw response from the client and returns whether the confirm button was pressed, without running any handlers.
	 *
	 * @throws FormValidationException
	 */
	public function parseResponse(mixed $data) : bool{
		if(!is_bool($data)){
			throw new FormValidationException("Expected bool for modal form response, got " . get_debug_type($data));
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
			"button1" => $this->confirmText instanceof Translatable ? $language->translate($this->confirmText) : $this->confirmText,
			"button2" => $this->cancelText instanceof Translatable ? $language->translate($this->cancelText) : $this->cancelText,
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
