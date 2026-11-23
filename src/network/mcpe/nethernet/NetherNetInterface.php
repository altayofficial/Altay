<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\nethernet;

use altay\netherlib\ipc\NetherLibToUserThreadMessageReceiver;
use altay\netherlib\ipc\UserToNetherLibThreadMessageSender;
use altay\netherlib\NetherNetException;
use altay\netherlib\ServerListener;
use pmmp\thread\ThreadSafeArray;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\AdvancedNetworkInterface;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\Network;
use pocketmine\network\NetworkInterfaceStartException;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\ThreadCrashException;
use pocketmine\utils\Utils;
use function addcslashes;
use function implode;
use function rtrim;

/**
 * Runs NetherLib on a thread of its own and turns what it reports into NetworkSessions.
 */
class NetherNetInterface implements AdvancedNetworkInterface, ServerListener{

	private Network $network;

	private ?NetherNetServerThread $thread = null;
	private ?UserToNetherLibThreadMessageSender $commandSender = null;
	private ?NetherLibToUserThreadMessageReceiver $eventReceiver = null;

	/** @var NetworkSession[] */
	private array $sessions = [];

	private SleeperHandlerEntry $sleeperEntry;

	public function __construct(
		private Server $server,
		private ThreadSafeLogger $logger,
		private NetherNetServerFactory $factory,
		private PacketBroadcaster $packetBroadcaster,
		private EntityEventBroadcaster $entityEventBroadcaster,
		private TypeConverter $typeConverter
	){
		$this->sleeperEntry = $this->server->getTickSleeper()->addNotifier(function() : void{
			$this->processEvents();
		});
	}

	public function start() : void{
		if($this->thread !== null){
			throw new \LogicException("NetherNet thread is already running");
		}

		/** @phpstan-var ThreadSafeArray<int, string> $mainToThreadBuffer */
		$mainToThreadBuffer = new ThreadSafeArray();
		/** @phpstan-var ThreadSafeArray<int, string> $threadToMainBuffer */
		$threadToMainBuffer = new ThreadSafeArray();

		$thread = new NetherNetServerThread(
			$this->logger,
			$mainToThreadBuffer,
			$threadToMainBuffer,
			$this->factory,
			$this->sleeperEntry
		);
		try{
			$thread->startAndWait();
		}catch(NetherNetException | ThreadCrashException $e){
			throw new NetworkInterfaceStartException($e->getMessage(), 0, $e);
		}

		$this->thread = $thread;
		$this->commandSender = new UserToNetherLibThreadMessageSender(new PthreadsChannelWriter($mainToThreadBuffer));
		$this->eventReceiver = new NetherLibToUserThreadMessageReceiver(new PthreadsChannelReader($threadToMainBuffer));
	}

	public function setNetwork(Network $network) : void{
		$this->network = $network;
	}

	public function tick() : void{
		if($this->thread !== null && !$this->thread->isRunning()){
			$e = $this->thread->getCrashInfo();
			if($e !== null){
				throw new ThreadCrashException("NetherNet thread crashed", $e);
			}
			throw new \RuntimeException("NetherNet thread crashed without crash information");
		}
		$this->processEvents();
	}

	private function processEvents() : void{
		if($this->eventReceiver !== null){
			while($this->eventReceiver->handle($this));
		}
	}

	public function shutdown() : void{
		if($this->thread !== null){
			$this->commandSender?->shutdown();
			$this->thread->quit();
			$this->thread = null;
		}
		$this->server->getTickSleeper()->removeNotifier($this->sleeperEntry->getNotifierId());
		$this->commandSender = null;
		$this->eventReceiver = null;
	}

	public function onSessionOpen(int $sessionId, string $address, int $port, ?string $authenticatedPublicKey) : void{
		if($this->commandSender === null){
			return;
		}
		$this->sessions[$sessionId] = new NetworkSession(
			$this->server,
			$this->network->getSessionManager(),
			PacketPool::getInstance(),
			new NetherNetPacketSender($sessionId, $this),
			$this->packetBroadcaster,
			$this->entityEventBroadcaster,
			ZlibCompressor::getInstance(),
			$this->typeConverter,
			$address,
			$port,
			$authenticatedPublicKey
		);
	}

	public function onSessionClose(int $sessionId, string $reason) : void{
		if(isset($this->sessions[$sessionId])){
			$networkSession = $this->sessions[$sessionId];
			unset($this->sessions[$sessionId]);
			$networkSession->onClientDisconnect(match($reason){
				"client disconnect" => KnownTranslationFactory::pocketmine_disconnect_clientDisconnect(),
				"timeout" => KnownTranslationFactory::pocketmine_disconnect_error_timeout(),
				"client reconnect" => KnownTranslationFactory::pocketmine_disconnect_clientReconnect(),
				default => "Disconnected: $reason"
			});
		}
	}

	/**
	 * @internal
	 */
	public function sendPacket(int $sessionId, string $payload, bool $immediate, ?int $receiptId) : void{
		if(isset($this->sessions[$sessionId])){
			$this->commandSender?->sendPacket($sessionId, $payload, $immediate, $receiptId);
		}
	}

	/**
	 * @internal
	 */
	public function close(int $sessionId) : void{
		if(isset($this->sessions[$sessionId])){
			unset($this->sessions[$sessionId]);
			$this->commandSender?->closeSession($sessionId);
		}
	}

	public function onPacketReceive(int $sessionId, string $payload) : void{
		if(isset($this->sessions[$sessionId])){
			if($payload === ""){
				return;
			}
			$networkSession = $this->sessions[$sessionId];
			$address = $networkSession->getIp();
			$name = $networkSession->getDisplayName();
			try{
				$networkSession->handleEncoded($payload);
			}catch(PacketHandlingException $e){
				$logger = $networkSession->getLogger();

				$networkSession->disconnectWithError(
					reason: "Bad packet: " . $e->getMessage(),
					disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error_badPacket()
				);
				$logger->debug(implode("\n", Utils::printableExceptionInfo($e)));

				$this->blockAddress($address, 5);
			}catch(\Throwable $e){
				$this->server->getLogger()->emergency("Crash occurred while handling a packet from session: $name");
				throw $e;
			}
		}
	}

	public function onPacketAck(int $sessionId, int $receiptId) : void{
		if(isset($this->sessions[$sessionId])){
			$this->sessions[$sessionId]->handleAckReceipt($receiptId);
		}
	}

	public function onPingUpdate(int $sessionId, int $pingMS) : void{
		if(isset($this->sessions[$sessionId])){
			$this->sessions[$sessionId]->updatePing($pingMS);
		}
	}

	public function onBandwidthUpdate(int $bytesSentDiff, int $bytesReceivedDiff) : void{
		$this->network->getBandwidthTracker()->add($bytesSentDiff, $bytesReceivedDiff);
	}

	public function blockAddress(string $address, int $timeout = 300) : void{
		$this->commandSender?->blockAddress($address, $timeout);
	}

	public function unblockAddress(string $address) : void{
		$this->commandSender?->unblockAddress($address);
	}

	public function sendRawPacket(string $address, int $port, string $payload) : void{
		//NOOP, libdatachannel owns the media socket and nothing else gets to send on it
	}

	public function addRawPacketFilter(string $regex) : void{
		//NOOP, see sendRawPacket()
	}

	public function setName(string $name) : void{
		$info = $this->server->getQueryInformation();

		//hmm, NetherNet has no use for most of this, but the server card is still read out of a RakNet style MOTD
		$this->commandSender?->setName(implode(";",
			[
				"MCPE",
				rtrim(addcslashes($name, ";"), '\\'),
				ProtocolInfo::CURRENT_PROTOCOL,
				ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				$info->getPlayerCount(),
				$info->getMaxPlayerCount(),
				0,
				rtrim(addcslashes($this->server->getSubMotd(), ";"), '\\'),
				match($this->server->getGamemode()){
					GameMode::SURVIVAL => "Survival",
					GameMode::ADVENTURE => "Adventure",
					default => "Creative"
				}
			]) . ";"
		);
	}
}
