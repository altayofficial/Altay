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

final class TextPrimitiveShape extends PrimitiveShape{

	public function __construct(
		int $networkId,
		Vector3 $location,
		private string $text,
		private bool $useRotation = false,
		private ?Color $backgroundColor = null,
		private bool $depthTest = true,
		private bool $showBackface = true,
		private bool $showTextBackface = true,
		?Color $color = null,
		?int $dimensionId = null,
		?int $attachedToEntityId = null
	){
		parent::__construct($networkId, $location, $color, $dimensionId, $attachedToEntityId);
	}

	public function getText() : string{ return $this->text; }

	public function usesRotation() : bool{ return $this->useRotation; }

	public function getBackgroundColor() : ?Color{ return $this->backgroundColor; }

	public function hasDepthTest() : bool{ return $this->depthTest; }

	public function showsBackface() : bool{ return $this->showBackface; }

	public function showsTextBackface() : bool{ return $this->showTextBackface; }

	protected function getType() : PrimitiveShapeType{ return PrimitiveShapeType::TEXT; }

	public function toShapeData() : PacketShapeData{
		return PacketShapeData::text($this->networkId, $this->location, $this->text, $this->useRotation, $this->backgroundColor, $this->depthTest, $this->showBackface, $this->showTextBackface, $this->color, $this->dimensionId, $this->attachedToEntityId);
	}
}
