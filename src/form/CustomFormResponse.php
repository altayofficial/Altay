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

use function array_key_exists;
use function get_debug_type;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Validated values of a submitted CustomForm, keyed by input name.
 * Toggles give bool, sliders float, dropdowns and step sliders the picked index as int, inputs string.
 */
final class CustomFormResponse{

	/**
	 * @param bool[]|int[]|float[]|string[] $values
	 * @phpstan-param array<string, bool|int|float|string> $values
	 */
	public function __construct(
		private array $values
	){}

	/**
	 * @return bool[]|int[]|float[]|string[]
	 * @phpstan-return array<string, bool|int|float|string>
	 */
	public function getAll() : array{ return $this->values; }

	public function has(string $name) : bool{
		return array_key_exists($name, $this->values);
	}

	/**
	 * @throws \InvalidArgumentException if there is no input with this name
	 */
	public function get(string $name) : bool|int|float|string{
		if(!array_key_exists($name, $this->values)){
			throw new \InvalidArgumentException("No input named \"$name\" in this response");
		}
		return $this->values[$name];
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function getBool(string $name) : bool{
		$value = $this->get($name);
		return is_bool($value) ? $value : throw self::wrongType($name, "bool", $value);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function getInt(string $name) : int{
		$value = $this->get($name);
		return is_int($value) ? $value : throw self::wrongType($name, "int", $value);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function getFloat(string $name) : float{
		$value = $this->get($name);
		return is_float($value) ? $value : throw self::wrongType($name, "float", $value);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function getString(string $name) : string{
		$value = $this->get($name);
		return is_string($value) ? $value : throw self::wrongType($name, "string", $value);
	}

	private static function wrongType(string $name, string $expected, mixed $value) : \InvalidArgumentException{
		return new \InvalidArgumentException("Input \"$name\" holds " . get_debug_type($value) . ", not $expected");
	}
}
