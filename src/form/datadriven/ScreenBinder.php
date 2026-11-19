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
use function array_values;
use function is_int;

/**
 * @internal
 * Keeps track of which paths of a screen's data tree are tied to which Observables, and which paths the client is
 * allowed to write to. One per session, thrown away when the session closes.
 */
final class ScreenBinder{
	/**
	 * @var \Closure[]
	 * @phpstan-var array<string, \Closure(string|float|bool $value) : void>
	 */
	private array $writeHandlers = [];
	/**
	 * @var \Closure[]
	 * @phpstan-var list<\Closure() : void>
	 */
	private array $releasers = [];
	/** Path the client is currently writing to, so we don't echo its own value straight back. */
	private ?string $writingPath = null;

	/**
	 * @phpstan-param \Closure(list<string> $paths, bool|int|string $value) : void $push
	 */
	public function __construct(
		private Player $player,
		private DataDrivenScreenSession $session,
		private \Closure $push
	){}

	public function getPlayer() : Player{ return $this->player; }

	public function getSession() : DataDrivenScreenSession{ return $this->session; }

	public function translate(string|Translatable $text) : string{
		return $text instanceof Translatable ? $this->player->getLanguage()->translate($text) : $text;
	}

	/**
	 * Returns the text to put in the initial tree. If it's an Observable, later changes are pushed to all $paths.
	 *
	 * @phpstan-param string|Translatable|Observable<string> $text
	 */
	public function text(string|Translatable|Observable $text, string ...$paths) : string{
		if($text instanceof Observable){
			$this->follow($text, array_values($paths));
			return $text->get();
		}
		return $this->translate($text);
	}

	/**
	 * @phpstan-param bool|Observable<bool> $flag
	 */
	public function flag(bool|Observable $flag, string ...$paths) : bool{
		if($flag instanceof Observable){
			$this->follow($flag, array_values($paths));
			return $flag->get();
		}
		return $flag;
	}

	/**
	 * Binds a client writable value. $accept gets the raw client value and returns the value to store, or null to
	 * reject it, in which case the client gets the current value back.
	 *
	 * @phpstan-template TValue of bool|int|string
	 * @phpstan-param Observable<TValue>                                    $observable
	 * @phpstan-param \Closure(string|float|bool $raw) : (TValue|null)      $accept
	 * @phpstan-return TValue
	 */
	public function value(Observable $observable, string $path, \Closure $accept) : bool|int|string{
		$this->follow($observable, [$path]);
		$this->writeHandlers[$path] = function(string|float|bool $raw) use ($observable, $path, $accept) : void{
			$accepted = $accept($raw);
			if($accepted === null){
				$this->player->getNetworkSession()->getLogger()->debug("Rejected data driven UI write to $path");
				($this->push)([$path], $observable->get());
				return;
			}
			$this->writingPath = $path;
			try{
				$observable->setFromClient($accepted, $this->player);
			}finally{
				$this->writingPath = null;
			}
			//hmm, sliders get snapped and clamped, so the client might be showing something we didn't store
			$changed = is_int($accepted) ? (float) $accepted !== $raw : $accepted !== $raw;
			if($changed){
				($this->push)([$path], $accepted);
			}
		};
		return $observable->get();
	}

	/**
	 * Registers a click target. The client bumps the value at $path when the button is pressed; we only care that
	 * it happened. Returns the initial value for the tree.
	 *
	 * @phpstan-param \Closure() : void $onTrigger
	 */
	public function action(string $path, \Closure $onTrigger) : int{
		$this->writeHandlers[$path] = function(string|float|bool $raw) use ($onTrigger) : void{
			$onTrigger();
		};
		return 0;
	}

	/**
	 * Routes a write from the client. Returns false if nothing is bound at that path.
	 */
	public function handleWrite(string $path, string|float|bool $value) : bool{
		$handler = $this->writeHandlers[$path] ?? null;
		if($handler === null){
			return false;
		}
		$handler($value);
		return true;
	}

	/**
	 * Drops every Observable subscription, so a closed session doesn't stay alive through a shared Observable.
	 */
	public function release() : void{
		foreach($this->releasers as $release){
			$release();
		}
		$this->releasers = [];
		$this->writeHandlers = [];
	}

	/**
	 * @phpstan-template TValue of bool|int|string
	 * @phpstan-param Observable<TValue> $observable
	 * @phpstan-param list<string>       $paths
	 */
	private function follow(Observable $observable, array $paths) : void{
		$listener = function(bool|int|string $value, ?Player $source) use ($paths) : void{
			$targets = [];
			foreach($paths as $path){
				if($path !== $this->writingPath){
					$targets[] = $path;
				}
			}
			if($targets !== []){
				($this->push)($targets, $value);
			}
		};
		$observable->getListeners()->add($listener);
		$this->releasers[] = fn() => $observable->getListeners()->remove($listener);
	}
}
