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

namespace pocketmine\item;

/**
 * Represents an armor trim pattern + material combination applied via a smithing table.
 * The client resolves the actual texture/colour from these two IDs using its resource pack data.
 */
final class ArmorTrim{

	public function __construct(
		private string $patternId,
		private string $materialId
	){}

	public function getPatternId() : string{
		return $this->patternId;
	}

	public function getMaterialId() : string{
		return $this->materialId;
	}

	public function equals(ArmorTrim $other) : bool{
		return $this->patternId === $other->patternId && $this->materialId === $other->materialId;
	}
}
