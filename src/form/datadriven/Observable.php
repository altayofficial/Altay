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

use pocketmine\player\Player;
use pocketmine\utils\ObjectSet;
use function get_debug_type;
use function gettype;

/**
 * A value that stays in sync between the server and every open screen bound to it. Share one instance between
 * several screens or players and they all see changes live. Values written by a client (toggles, sliders, text
 * fields...) land here too, already validated by the element that owns them.
 *
 * @phpstan-template TValue of bool|int|string
 */
final class Observable{
	/**
	 * @var \Closure[]|ObjectSet
	 * @phpstan-var ObjectSet<\Closure(TValue $newValue, ?Player $source) : void>
	 */
	private ObjectSet $listeners;

	/**
	 * @phpstan-param TValue $value
	 */
	private function __construct(
		private bool|int|string $value
	){
		$this->listeners = new ObjectSet();
	}

	/** @phpstan-return self<bool> */
	public static function bool(bool $initial) : self{
		return new self($initial);
	}

	/** @phpstan-return self<int> */
	public static function int(int $initial) : self{
		return new self($initial);
	}

	/** @phpstan-return self<string> */
	public static function string(string $initial) : self{
		return new self($initial);
	}

	/**
	 * @phpstan-return TValue
	 */
	public function get() : bool|int|string{
		return $this->value;
	}

	/**
	 * Changes the value and pushes it to every screen showing it.
	 *
	 * @phpstan-param TValue $value
	 * @throws \TypeError if the value is not the same type the observable was created with
	 */
	public function set(bool|int|string $value) : void{
		$this->update($value, null);
	}

	/**
	 * @internal
	 * Same as set(), but remembers which player caused it so listeners can tell the two apart.
	 *
	 * @phpstan-param TValue $value
	 */
	public function setFromClient(bool|int|string $value, Player $source) : void{
		$this->update($value, $source);
	}

	/**
	 * Listeners run on every change, with the player who made it, or null when the server did.
	 *
	 * @return \Closure[]|ObjectSet
	 * @phpstan-return ObjectSet<\Closure(TValue $newValue, ?Player $source) : void>
	 */
	public function getListeners() : ObjectSet{
		return $this->listeners;
	}

	/**
	 * @phpstan-param TValue $value
	 */
	private function update(bool|int|string $value, ?Player $source) : void{
		//yeah, phpstan generics vanish at runtime, so a plugin can still sneak an int into a string observable
		if(gettype($value) !== gettype($this->value)){
			throw new \TypeError("Expected " . get_debug_type($this->value) . ", got " . get_debug_type($value));
		}
		if($value === $this->value){
			return;
		}
		$this->value = $value;
		foreach($this->listeners as $listener){
			$listener($value, $source);
		}
	}
}
