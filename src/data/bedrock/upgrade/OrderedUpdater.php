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

use function count;

final class OrderedUpdater{

	private static ?self $facingToBlock = null;
	private static ?self $facingToCardinal = null;
	private static ?self $directionToCardinal = null;

	public static function facingToBlock() : self{
		return self::$facingToBlock ??= new self(
			"facing_direction", "minecraft:block_face", 0,
			["down", "up", "north", "south", "west", "east"]
		);
	}

	public static function facingToCardinal() : self{
		return self::$facingToCardinal ??= new self(
			"facing_direction", "minecraft:cardinal_direction", 2,
			["north", "south", "west", "east"]
		);
	}

	public static function directionToCardinal() : self{
		return self::$directionToCardinal ??= new self(
			"direction", "minecraft:cardinal_direction", 0,
			["south", "west", "north", "east"]
		);
	}

	/**
	 * @param string[] $order
	 * @phpstan-param list<string> $order
	 */
	public function __construct(
		private string $oldProperty,
		private string $newProperty,
		private int $offset,
		private array $order
	){
		if(count($order) < 1){
			throw new \InvalidArgumentException("empty order array");
		}
	}

	public function getOldProperty() : string{
		return $this->oldProperty;
	}

	public function getNewProperty() : string{
		return $this->newProperty;
	}

	public function translate(int $value) : string{
		$index = $value - $this->offset;
		if($index < 0 || $index >= count($this->order)){
			$index = 0;
		}
		return $this->order[$index];
	}
}
