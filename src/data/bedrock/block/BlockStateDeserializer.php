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

namespace pocketmine\data\bedrock\block;

/**
 * Implementors of this interface decide how a block should be deserialized and represented at runtime. This is used by
 * world providers when decoding blockstates into block IDs.
 *
 * @phpstan-type BlockStateId int
 */
interface BlockStateDeserializer{
	/**
	 * Deserializes blockstate NBT into an implementation-defined blockstate ID, for runtime paletted storage.
	 *
	 * @phpstan-return BlockStateId
	 * @throws BlockStateDeserializeException
	 */
	public function deserialize(BlockStateData $stateData) : int;
}
