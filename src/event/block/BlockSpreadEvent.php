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

namespace pocketmine\event\block;

use pocketmine\block\Block;

/**
 * Called when a block spreads to another block, such as grass spreading to nearby dirt blocks.
 */
class BlockSpreadEvent extends BaseBlockChangeEvent{

	/**
	 * @param Block $block    Block being replaced (TODO: rename this)
	 * @param Block $source   Origin of the spread
	 * @param Block $newState Replacement block
	 */
	public function __construct(
		Block $block,
		private Block $source,
		Block $newState
	){
		parent::__construct($block, $newState);
	}

	public function getSource() : Block{
		return $this->source;
	}
}
