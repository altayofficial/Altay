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

namespace pocketmine\utils;

use function array_keys;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Handles parsing any Minecraft thing from strings. This can be used, for example, to implement a user-friendly item
 * parser to be used by the /give command (and others).
 * Custom aliases may be registered.
 * Note that the aliases should be user-friendly, i.e. easily readable and writable.
 *
 * @phpstan-template T
 */
abstract class StringToTParser{

	/**
	 * @var \Closure[]
	 * @phpstan-var array<string, \Closure(string $input) : T>
	 */
	private array $callbackMap = [];

	/** @phpstan-param \Closure(string $input) : T $callback */
	public function register(string $alias, \Closure $callback) : void{
		$key = $this->reprocess($alias);
		if(isset($this->callbackMap[$key])){
			throw new \InvalidArgumentException("Alias \"$key\" is already registered");
		}
		$this->callbackMap[$key] = $callback;
	}

	/** @phpstan-param \Closure(string $input) : T $callback */
	public function override(string $alias, \Closure $callback) : void{
		$this->callbackMap[$this->reprocess($alias)] = $callback;
	}

	/**
	 * Registers a new alias for an existing known alias.
	 */
	public function registerAlias(string $existing, string $alias) : void{
		$existingKey = $this->reprocess($existing);
		if(!isset($this->callbackMap[$existingKey])){
			throw new \InvalidArgumentException("Cannot register new alias for unknown existing alias \"$existing\"");
		}
		$newKey = $this->reprocess($alias);
		if(isset($this->callbackMap[$newKey])){
			throw new \InvalidArgumentException("Alias \"$newKey\" is already registered");
		}
		$this->callbackMap[$newKey] = $this->callbackMap[$existingKey];
	}

	/**
	 * Tries to parse the specified string into a corresponding instance of T.
	 * @phpstan-return T|null
	 */
	public function parse(string $input){
		$key = $this->reprocess($input);
		if(isset($this->callbackMap[$key])){
			return ($this->callbackMap[$key])($input);
		}

		return null;
	}

	protected function reprocess(string $input) : string{
		return strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($input)));
	}

	/** @return string[]|int[] */
	public function getKnownAliases() : array{
		return array_keys($this->callbackMap);
	}
}
