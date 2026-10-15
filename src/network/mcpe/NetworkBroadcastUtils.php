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

namespace pocketmine\network\mcpe;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;
use pocketmine\timings\Timings;
use function count;
use function spl_object_id;

final class NetworkBroadcastUtils{

	private function __construct(){
		//NOOP
	}

	/**
	 * @param Player[]            $recipients
	 * @param ClientboundPacket[] $packets
	 */
	public static function broadcastPackets(array $recipients, array $packets) : bool{
		if(count($packets) === 0){
			throw new \InvalidArgumentException("Cannot broadcast empty list of packets");
		}

		//every entity movement goes through here once per tick with all its viewers, so this stays a single pass
		//without the closure Timings::time() would need
		Timings::$broadcastPackets->startTiming();
		try{
			/** @phpstan-var array<int, PacketBroadcaster> $uniqueBroadcasters */
			$uniqueBroadcasters = [];
			/** @phpstan-var array<int, array<int, NetworkSession>> $broadcasterTargets */
			$broadcasterTargets = [];
			foreach($recipients as $player){
				$session = $player->getConnectedNetworkSession();
				if($session === null){
					continue;
				}
				$broadcaster = $session->getBroadcaster();
				$broadcasterId = spl_object_id($broadcaster);
				$uniqueBroadcasters[$broadcasterId] = $broadcaster;
				$broadcasterTargets[$broadcasterId][spl_object_id($session)] = $session;
			}
			if(count($uniqueBroadcasters) === 0){
				return false;
			}

			foreach($uniqueBroadcasters as $broadcasterId => $broadcaster){
				$broadcaster->broadcastPackets($broadcasterTargets[$broadcasterId], $packets);
			}

			return true;
		}finally{
			Timings::$broadcastPackets->stopTiming();
		}
	}

	/**
	 * @param Player[] $recipients
	 * @phpstan-param \Closure(EntityEventBroadcaster, array<int, NetworkSession>) : void $callback
	 */
	public static function broadcastEntityEvent(array $recipients, \Closure $callback) : void{
		$uniqueBroadcasters = [];
		$broadcasterTargets = [];

		foreach($recipients as $recipient){
			$session = $recipient->getNetworkSession();
			$broadcaster = $session->getEntityEventBroadcaster();
			$uniqueBroadcasters[spl_object_id($broadcaster)] = $broadcaster;
			$broadcasterTargets[spl_object_id($broadcaster)][spl_object_id($session)] = $session;
		}

		foreach($uniqueBroadcasters as $k => $broadcaster){
			$callback($broadcaster, $broadcasterTargets[$k]);
		}
	}
}
