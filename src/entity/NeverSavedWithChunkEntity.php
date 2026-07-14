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

namespace pocketmine\entity;

/**
 * Decorator for entities that will never be saved with a chunk.
 * Entities implementing this interface are not required to register a save ID.
 *
 * This differs from {@link Entity::canSaveWithChunk()} because it can't be changed after the entity is created.
 * We can't use canSaveWithChunk() to decide whether an entity needs a save ID, but we can use an interface like this.
 * An attribute would also work, but `instanceof NonSaveable` is easier.
 */
interface NeverSavedWithChunkEntity{

}
