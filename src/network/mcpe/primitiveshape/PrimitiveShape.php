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

namespace pocketmine\network\mcpe\primitiveshape;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PrimitiveShapesPacket;
use pocketmine\network\mcpe\protocol\types\PacketShapeData;
use pocketmine\network\mcpe\protocol\types\PrimitiveShapeType;
use function array_map;
use function array_values;

abstract class PrimitiveShape{

	public function __construct(
		protected int $networkId,
		protected Vector3 $location,
		protected ?Color $color = null,
		protected ?int $dimensionId = null,
		protected ?int $attachedToEntityId = null
	){}

	public function getNetworkId() : int{ return $this->networkId; }

	public function getShapeType() : PrimitiveShapeType{ return $this->getType(); }

	public function getLocation() : Vector3{ return $this->location; }

	public function getColor() : ?Color{ return $this->color; }

	public function getDimensionId() : ?int{ return $this->dimensionId; }

	public function getAttachedToEntityId() : ?int{ return $this->attachedToEntityId; }

	abstract protected function getType() : PrimitiveShapeType;

	abstract public function toShapeData() : PacketShapeData;

	public static function createPacket(PrimitiveShape ...$shapes) : PrimitiveShapesPacket{
		return PrimitiveShapesPacket::create(array_values(array_map(fn(PrimitiveShape $shape) => $shape->toShapeData(), $shapes)));
	}

	public static function createRemovePacket(int ...$networkIds) : PrimitiveShapesPacket{
		return PrimitiveShapesPacket::create(array_values(array_map(fn(int $networkId) => PacketShapeData::remove($networkId), $networkIds)));
	}
}
