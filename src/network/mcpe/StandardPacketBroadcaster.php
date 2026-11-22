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

use pmmp\encoding\ByteBufferWriter;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\Server;
use pocketmine\timings\Timings;
use function count;
use function log;
use function spl_object_id;
use function strlen;

final class StandardPacketBroadcaster implements PacketBroadcaster{
	public function __construct(
		private Server $server
	){}

	public function broadcastPackets(array $recipients, array $packets) : void{
		//TODO: this shouldn't really be called here, since the broadcaster might be replaced by an alternative
		//implementation that doesn't fire events
		if(DataPacketSendEvent::hasHandlers()){
			$ev = new DataPacketSendEvent($recipients, $packets);
			$ev->call();
			if($ev->isCancelled()){
				return;
			}
			$packets = $ev->getPackets();
		}

		$totalLength = 0;
		$packetBuffers = [];
		$writer = new ByteBufferWriter();
		foreach($packets as $packet){
			$writer->clear(); //memory reuse let's gooooo
			$buffer = NetworkSession::encodePacketTimed($writer, $packet);
			//varint length prefix + packet buffer
			$totalLength += (((int) log(strlen($buffer), 128)) + 1) + strlen($buffer);
			$packetBuffers[] = $buffer;
		}
		$singlePacketBuffer = count($packetBuffers) === 1 ? $packetBuffers[0] : null;

		//this runs for every viewer of every moving entity. most of the time everyone shares one compressor and the
		//packets are too small to be worth a shared compressed batch, so those are sent in the same pass
		$lastCompressor = null;
		$lastSendsDirectly = false;
		$compressors = [];
		$targetsByCompressor = [];
		foreach($recipients as $recipient){
			$compressor = $recipient->getCompressor();
			if($compressor !== $lastCompressor){
				$lastCompressor = $compressor;
				$threshold = $compressor->getCompressionThreshold();
				$lastSendsDirectly = $threshold === null || $totalLength < $threshold;
			}

			if($lastSendsDirectly){
				if($singlePacketBuffer !== null){
					$recipient->addToSendBuffer($singlePacketBuffer);
				}else{
					foreach($packetBuffers as $packetBuffer){
						$recipient->addToSendBuffer($packetBuffer);
					}
				}
			}else{
				//TODO: different compressors might be compatible, it might not be necessary to split them up by object
				$compressors[spl_object_id($compressor)] = $compressor;
				$targetsByCompressor[spl_object_id($compressor)][] = $recipient;
			}
		}

		foreach($targetsByCompressor as $compressorId => $compressorTargets){
			if(count($compressorTargets) > 1){
				//do not prepare shared batch unless we're sure it will be compressed
				$stream = new ByteBufferWriter();
				PacketBatch::encodeRaw($stream, $packetBuffers);
				$batchBuffer = $stream->getData();

				$batch = $this->server->prepareBatch($batchBuffer, $compressors[$compressorId], timings: Timings::$playerNetworkSendCompressBroadcast);
				foreach($compressorTargets as $target){
					$target->queueCompressed($batch);
				}
			}else{
				foreach($compressorTargets as $target){
					foreach($packetBuffers as $packetBuffer){
						$target->addToSendBuffer($packetBuffer);
					}
				}
			}
		}
	}
}
