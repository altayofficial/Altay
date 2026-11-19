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

use pocketmine\block\utils\HorizontalFacingOption;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use function in_array;
use function spl_object_id;

class Tripwire extends Flowable implements StateDeriving{
	protected bool $triggered = false;
	protected bool $suspended = false; //unclear usage, makes hitbox bigger if set
	protected bool $connected = false;
	protected bool $disarmed = false;
	/**
	 * @var HorizontalFacingOption[]
	 * @phpstan-var array<int, HorizontalFacingOption>
	 */
	protected array $connections = [];

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->triggered);
		$w->bool($this->suspended);
		$w->bool($this->connected);
		$w->bool($this->disarmed);
		$w->enumSet($this->connections, HorizontalFacingOption::cases());
	}

	public function isTriggered() : bool{ return $this->triggered; }

	/** @return $this */
	public function setTriggered(bool $triggered) : self{
		$this->triggered = $triggered;
		return $this;
	}

	public function isSuspended() : bool{ return $this->suspended; }

	/** @return $this */
	public function setSuspended(bool $suspended) : self{
		$this->suspended = $suspended;
		return $this;
	}

	public function isConnected() : bool{ return $this->connected; }

	/** @return $this */
	public function setConnected(bool $connected) : self{
		$this->connected = $connected;
		return $this;
	}

	public function isDisarmed() : bool{ return $this->disarmed; }

	/** @return $this */
	public function setDisarmed(bool $disarmed) : self{
		$this->disarmed = $disarmed;
		return $this;
	}

	public function isConnectedTo(Facing $facing) : bool{
		return isset($this->connections[spl_object_id(HorizontalFacingOption::fromFacing($facing))]);
	}

	/** @return $this */
	public function setConnection(Facing $facing, bool $connected) : self{
		if(!in_array($facing, Facing::HORIZONTAL, true)){
			throw new \InvalidArgumentException("Facing must be horizontal");
		}
		if($connected){
			$option = HorizontalFacingOption::fromFacing($facing);
			$this->connections[spl_object_id($option)] = $option;
		}else{
			unset($this->connections[spl_object_id(HorizontalFacingOption::fromFacing($facing))]);
		}
		return $this;
	}

	public function onNearbyBlockChange() : void{
		if($this->deriveStateFromWorld()){
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function deriveStateFromWorld() : bool{
		$changed = false;
		foreach(Facing::HORIZONTAL as $facing){
			$side = $this->getSide($facing);
			$connected = $side instanceof Tripwire || $side instanceof TripwireHook;
			if($connected !== isset($this->connections[spl_object_id(HorizontalFacingOption::fromFacing($facing))])){
				$this->setConnection($facing, $connected);
				$changed = true;
			}
		}
		return $changed;
	}

	public function asItem() : Item{
		return VanillaItems::STRING();
	}
}
