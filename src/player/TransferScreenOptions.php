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

namespace pocketmine\player;

/**
 * Customises the screen the client shows while it's being transferred to another server.
 * Anything left as null keeps the client's own default, so you only need to set what you want to change.
 */
final class TransferScreenOptions{

	public function __construct(
		private ?string $titleText = null,
		private ?string $bodyText = null,
		private ?bool $titleVisible = null,
		private ?bool $bodyVisible = null,
		private ?string $cancelButtonText = null,
		private ?bool $cancelButtonVisible = null,
		private ?float $cancelButtonDelaySeconds = null
	){
		if($cancelButtonDelaySeconds !== null && $cancelButtonDelaySeconds < 0){
			throw new \InvalidArgumentException("Cancel button delay must not be negative, got $cancelButtonDelaySeconds");
		}
	}

	public function getTitleText() : ?string{ return $this->titleText; }

	public function getBodyText() : ?string{ return $this->bodyText; }

	public function isTitleVisible() : ?bool{ return $this->titleVisible; }

	public function isBodyVisible() : ?bool{ return $this->bodyVisible; }

	public function getCancelButtonText() : ?string{ return $this->cancelButtonText; }

	public function isCancelButtonVisible() : ?bool{ return $this->cancelButtonVisible; }

	/**
	 * How long the client waits before showing the cancel button, in seconds.
	 */
	public function getCancelButtonDelaySeconds() : ?float{ return $this->cancelButtonDelaySeconds; }
}
