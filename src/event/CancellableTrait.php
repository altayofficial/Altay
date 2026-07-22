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

namespace pocketmine\event;

/**
 * This trait provides a basic boolean-setter-style implementation for `Cancellable` to reduce boilerplate.
 * The precise meaning of `setCancelled` is subject to definition by the class using this trait.
 *
 * Implementors of `Cancellable` are not required to use this trait.
 *
 * @see Cancellable
 */
trait CancellableTrait{
	/** @var bool */
	private $isCancelled = false;

	public function isCancelled() : bool{
		return $this->isCancelled;
	}

	public function cancel() : void{
		$this->isCancelled = true;
	}

	public function uncancel() : void{
		$this->isCancelled = false;
	}
}
