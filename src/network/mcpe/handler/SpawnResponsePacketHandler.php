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

namespace pocketmine\network\mcpe\handler;

use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ServerboundLoadingScreenPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;

#[SilentDiscard(EmoteListPacket::class, comment: "Probably not needed?")]
#[SilentDiscard(InteractPacket::class, comment: "Player interacting with itself somehow")]
#[SilentDiscard(MobEquipmentPacket::class, comment: "Player equipping its held item on spawn, not needed")]
#[SilentDiscard(PlayerAuthInputPacket::class, comment: "Spammed after StartGame even though player has no controls")]
#[SilentDiscard(ServerboundLoadingScreenPacket::class, comment: "Not used, arrives with SetLocalPlayerAsInitialized")]
final class SpawnResponsePacketHandler extends PacketHandler{
	/**
	 * @phpstan-param \Closure() : void $responseCallback
	 */
	public function __construct(private \Closure $responseCallback){}

	public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet) : bool{
		($this->responseCallback)();
		return true;
	}
}
