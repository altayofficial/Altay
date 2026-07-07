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
use pocketmine\network\mcpe\protocol\types\PacketShapeData;
use pocketmine\network\mcpe\protocol\types\PrimitiveShapeType;

final class CirclePrimitiveShape extends PrimitiveShape{

	public const DEFAULT_SEGMENTS = 20;

	public function __construct(
		int $networkId,
		Vector3 $location,
		private float $scale,
		private int $segments = self::DEFAULT_SEGMENTS,
		?Color $color = null,
		?int $dimensionId = null,
		?int $attachedToEntityId = null
	){
		parent::__construct($networkId, $location, $color, $dimensionId, $attachedToEntityId);
	}

	public function getScale() : float{ return $this->scale; }

	public function getSegments() : int{ return $this->segments; }

	protected function getType() : PrimitiveShapeType{ return PrimitiveShapeType::CIRCLE; }

	public function toShapeData() : PacketShapeData{
		return PacketShapeData::circle($this->networkId, $this->location, $this->segments, $this->scale, $this->color, $this->dimensionId, $this->attachedToEntityId);
	}
}
