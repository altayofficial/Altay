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

namespace pocketmine\block\inventory\window;

use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class DoubleChestInventoryWindow extends BlockInventoryWindow{

	public function __construct(
		Player $viewer,
		Inventory $inventory,
		private Position $left,
		private Position $right
	){
		parent::__construct($viewer, $inventory, $this->left);
	}

	public function getLeft() : Position{ return $this->left; }

	public function getRight() : Position{ return $this->right; }
}
