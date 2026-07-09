<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\upgrade;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use function is_scalar;

final class CompoundTagUpdater{
	private CompoundTagUpdaterBuilder $builder;

	/**
	 * @var \Closure[]
	 * @phpstan-var list<\Closure(CompoundTagEditHelper) : bool>
	 */
	private array $filters = [];

	/**
	 * @var \Closure[]
	 * @phpstan-var list<\Closure(CompoundTagEditHelper) : void>
	 */
	private array $updaters = [];

	public function __construct(
		private int $version
	){
		$this->builder = new CompoundTagUpdaterBuilder($this);
	}

	public static function getTagValue(?Tag $tag) : string{
		if($tag === null){
			return "END";
		}
		if($tag instanceof StringTag){
			return $tag->getValue();
		}
		$value = $tag->getValue();
		return is_scalar($value) ? (string) $value : "END";
	}

	public function getVersion() : int{
		return $this->version;
	}

	public function update(CompoundTag $tag) : bool{
		$filterHelper = new CompoundTagEditHelper($tag);
		foreach($this->filters as $filter){
			if(!$filter($filterHelper)){
				return false;
			}
		}

		$updaterHelper = new CompoundTagEditHelper($tag);
		foreach($this->updaters as $updater){
			$updater($updaterHelper);
		}
		return true;
	}

	/**
	 * @internal
	 * @phpstan-param \Closure(CompoundTagEditHelper) : bool $filter
	 */
	public function addFilter(\Closure $filter) : void{
		$this->filters[] = $filter;
	}

	/**
	 * @internal
	 * @phpstan-param \Closure(CompoundTagEditHelper) : void $updater
	 */
	public function addUpdate(\Closure $updater) : void{
		$this->updaters[] = $updater;
	}

	public function builder() : CompoundTagUpdaterBuilder{
		return $this->builder;
	}
}
