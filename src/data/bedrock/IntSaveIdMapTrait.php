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

namespace pocketmine\data\bedrock;

use function array_key_exists;
use function spl_object_id;

/**
 * @phpstan-template TObject of object
 */
trait IntSaveIdMapTrait{

	/**
	 * @var object[]
	 * @phpstan-var array<int, TObject>
	 */
	private array $idToEnum = [];

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $enumToId = [];

	/**
	 * @phpstan-param TObject $enum
	 */
	protected function getRuntimeId(object $enum) : int{
		//this is fine for enums and non-cloning object registries
		return spl_object_id($enum);
	}

	/**
	 * @phpstan-param TObject $enum
	 */
	public function register(int $saveId, object $enum) : void{
		$this->idToEnum[$saveId] = $enum;
		$this->enumToId[$this->getRuntimeId($enum)] = $saveId;
	}

	/**
	 * @phpstan-return TObject|null
	 */
	public function fromId(int $id) : ?object{
		//we might not have all the effect IDs registered
		return $this->idToEnum[$id] ?? null;
	}

	/**
	 * @phpstan-param TObject $enum
	 */
	public function toId(object $enum) : int{
		$runtimeId = $this->getRuntimeId($enum);
		if(!array_key_exists($runtimeId, $this->enumToId)){
			//this should never happen, so we treat it as an exceptional condition
			throw new \InvalidArgumentException("Object does not have a mapped save ID");
		}
		return $this->enumToId[$runtimeId];
	}
}
