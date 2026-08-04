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
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\block\upgrade\model;

use function count;

final class BlockStateUpgradeSchemaModelFlattenInfo implements \JsonSerializable{

	/** @required */
	public string $prefix;
	/** @required */
	public string $flattenedProperty;
	public ?string $flattenedPropertyType = null;
	/** @required */
	public string $suffix;
	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	public array $flattenedValueRemaps;

	/**
	 * @param string[] $flattenedValueRemaps
	 * @phpstan-param array<string, string> $flattenedValueRemaps
	 */
	public function __construct(string $prefix, string $flattenedProperty, string $suffix, array $flattenedValueRemaps, ?string $flattenedPropertyType = null){
		$this->prefix = $prefix;
		$this->flattenedProperty = $flattenedProperty;
		$this->suffix = $suffix;
		$this->flattenedValueRemaps = $flattenedValueRemaps;
		$this->flattenedPropertyType = $flattenedPropertyType;
	}

	/**
	 * @return mixed[]
	 */
	public function jsonSerialize() : array{
		$result = (array) $this;
		if(count($this->flattenedValueRemaps) === 0){
			unset($result["flattenedValueRemaps"]);
		}
		if($this->flattenedPropertyType === null){
			unset($result["flattenedPropertyType"]);
		}
		return $result;
	}
}
