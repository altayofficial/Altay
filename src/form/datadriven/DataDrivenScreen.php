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

namespace pocketmine\form\datadriven;

use pocketmine\player\Player;
use pocketmine\utils\Utils;

/**
 * Base of the screens the client renders from synced data (CustomScreen, MessageScreen). Unlike Form, the client
 * keeps talking to the server while the screen is open, so values update live in both directions.
 *
 * Extending this class outside the server is not supported, use one of the final screens instead.
 */
abstract class DataDrivenScreen{
	/** @phpstan-var (\Closure(Player, DataDrivenScreenCloseReason) : void)|null */
	private ?\Closure $onClose = null;
	private bool $locked = false;

	/**
	 * Called once per player every time this screen closes for them, whatever the reason.
	 *
	 * @phpstan-param \Closure(Player $player, DataDrivenScreenCloseReason $reason) : void $onClose
	 * @return $this
	 */
	public function onClose(\Closure $onClose) : static{
		Utils::validateCallableSignature(function(Player $player, DataDrivenScreenCloseReason $reason) : void{}, $onClose);
		$this->onClose = $onClose;
		return $this;
	}

	/**
	 * Opens the screen for the player, closing whatever data driven screen they had open. The same instance can
	 * be shown to many players at once; Observables shared between them stay in sync for everyone.
	 *
	 * Nothing is opened if the player is offline, or if this is called from an onClose fired because another screen
	 * is replacing this one. The returned session is then already closed and onClose never fires for it.
	 *
	 * The layout is frozen after the first call, later changes have to go through Observables.
	 */
	final public function show(Player $player) : DataDrivenScreenSession{
		$this->validate();
		$this->locked = true;
		return $player->openDataDrivenScreen($this);
	}

	/**
	 * @throws \LogicException if the screen was already shown
	 */
	protected function checkNotLocked() : void{
		if($this->locked){
			throw new \LogicException("Cannot change the layout of a screen that has already been shown, use Observables for live changes");
		}
	}

	/**
	 * Last chance to complain before the screen goes out, e.g. a required part was never set.
	 *
	 * @throws \LogicException
	 */
	protected function validate() : void{

	}

	/**
	 * @internal
	 * Screen type the client opens, like "minecraft:custom_form".
	 */
	abstract public function getScreenId() : string;

	/**
	 * @internal
	 * Prefix of the data store property the client reads this screen's tree from. Each session appends its own
	 * instance id to it.
	 */
	abstract public function getPropertyName() : string;

	/**
	 * @internal
	 * Builds the whole data tree for one player.
	 *
	 * @return mixed[]
	 * @phpstan-return array<string, mixed>
	 */
	abstract public function compile(ScreenBinder $binder) : array;

	/**
	 * @internal
	 */
	public function onSessionClosed(Player $player, DataDrivenScreenCloseReason $reason) : void{
		if($this->onClose !== null){
			($this->onClose)($player, $reason);
		}
	}
}
