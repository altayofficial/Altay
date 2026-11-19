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

/**
 * One screen open for one player. You get it back from DataDrivenScreen::show() and from button callbacks.
 * Once closed it stays closed; show the screen again for a new session.
 */
final class DataDrivenScreenSession{
	private bool $open = false;
	private ScreenBinder $binder;
	private string $property;

	/**
	 * @internal
	 */
	public function __construct(
		private Player $player,
		private DataDrivenScreen $screen,
		private int $id
	){
		//ok so the client reads "<property>_<data instance id>", and we just use the session id as the instance id
		$this->property = $screen->getPropertyName() . "_" . $id;
		$this->binder = new ScreenBinder($player, $this, function(array $paths, bool|int|string $value) : void{
			if($this->open){
				$this->player->getNetworkSession()->onDataDrivenScreenValue($this->property, $paths, $value);
			}
		});
	}

	public function getPlayer() : Player{ return $this->player; }

	public function getScreen() : DataDrivenScreen{ return $this->screen; }

	public function isOpen() : bool{ return $this->open; }

	/**
	 * Closes the screen on the client with reason Programmatic. Does nothing if it's already closed.
	 */
	public function close() : void{
		$this->finish(DataDrivenScreenCloseReason::Programmatic, true);
	}

	/**
	 * @internal
	 */
	public function getId() : int{ return $this->id; }

	/**
	 * @internal
	 */
	public function open() : void{
		$data = $this->screen->compile($this->binder);
		$this->open = true;
		$this->player->getNetworkSession()->onDataDrivenScreenOpen($this->id, $this->screen->getScreenId(), $this->property, $data);
	}

	/**
	 * @internal
	 * Applies a value the client wrote. Returns false if the write doesn't belong to this screen.
	 */
	public function handleClientWrite(string $property, string $path, string|float|bool $value) : bool{
		if(!$this->open || $property !== $this->property){
			return false;
		}
		return $this->binder->handleWrite($path, $value);
	}

	/**
	 * @internal
	 * Tears the session down and fires the screen's onClose. $notifyClient is false when the client already knows,
	 * i.e. it told us it closed the screen, or it's gone.
	 */
	public function finish(DataDrivenScreenCloseReason $reason, bool $notifyClient) : void{
		if(!$this->open){
			return;
		}
		$this->open = false;
		$this->binder->release();
		if($notifyClient && $this->player->isConnected()){
			$this->player->getNetworkSession()->onDataDrivenScreenClose($this->id);
		}
		$this->screen->onSessionClosed($this->player, $reason);
	}
}
