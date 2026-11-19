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

namespace pocketmine\block\utils;

use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use function spl_object_id;

trait HorizontalConnectionsTrait{

	/**
	 * @var HorizontalFacingOption[]
	 * @phpstan-var array<int, HorizontalFacingOption>
	 */
	protected array $connections = [];

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enumSet($this->connections, HorizontalFacingOption::cases());
	}

	/**
	 * @return HorizontalFacingOption[]
	 * @phpstan-return array<int, HorizontalFacingOption>
	 */
	public function getConnections() : array{ return $this->connections; }

	public function isConnected(Facing $facing) : bool{
		return isset($this->connections[spl_object_id(HorizontalFacingOption::fromFacing($facing))]);
	}

	/** @return $this */
	public function setConnected(Facing $facing, bool $value) : self{
		$option = HorizontalFacingOption::fromFacing($facing);
		if($value){
			$this->connections[spl_object_id($option)] = $option;
		}else{
			unset($this->connections[spl_object_id($option)]);
		}
		return $this;
	}

	/**
	 * @param HorizontalFacingOption[] $connections
	 * @phpstan-param array<HorizontalFacingOption> $connections
	 * @return $this
	 */
	public function setConnections(array $connections) : self{
		$uniqueConnections = [];
		foreach($connections as $option){
			$uniqueConnections[spl_object_id($option)] = $option;
		}
		$this->connections = $uniqueConnections;
		return $this;
	}

	/**
	 * Recalculates the connections to the block's horizontal neighbours.
	 * Returns whether anything changed.
	 */
	protected function recalculateConnections() : bool{
		$changed = false;
		foreach(Facing::HORIZONTAL as $facing){
			$connected = $this->canConnectTo($facing);
			if($connected !== $this->isConnected($facing)){
				$this->setConnected($facing, $connected);
				$changed = true;
			}
		}

		if($changed){
			$this->collisionBoxes = null;
		}
		return $changed;
	}

	abstract protected function canConnectTo(Facing $facing) : bool;
}
