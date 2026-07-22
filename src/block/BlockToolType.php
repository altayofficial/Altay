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

namespace pocketmine\block;

/**
 * Types of tools that can be used to break blocks
 * Blocks may allow multiple tool types by combining these bitflags
 */
final class BlockToolType{

	private function __construct(){
		//NOOP
	}

	public const NONE = 0;
	public const SWORD = 1 << 0;
	public const SHOVEL = 1 << 1;
	public const PICKAXE = 1 << 2;
	public const AXE = 1 << 3;
	public const SHEARS = 1 << 4;
	public const HOE = 1 << 5;

}
