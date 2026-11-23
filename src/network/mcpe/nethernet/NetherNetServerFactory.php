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

use altay\netherlib\auth\TokenTrust;
use altay\netherlib\Credentials;
use altay\netherlib\IceServer;
use altay\netherlib\NetherNetServer;
use altay\netherlib\ServerData;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class NetherNetServerFactory{

	/**
	 * @param string[] $iceServers
	 * @param string[] $iceInterfaces
	 * @param string[] $advertisedAddresses
	 * @param array{int, int}|null $udpPortRange
	 */
	public function __construct(
		private int $networkId,
		private string $motd,
		private string $levelName,
		private int $maxPlayerCount,
		private string $bindAddress = "0.0.0.0",
		private int $port = NetherNetServer::DISCOVERY_PORT,
		private bool $onlineMode = false,
		private int $signallingPort = 19132,
		private ?string $identityKeyPath = null,
		private string $identityDomain = "self",
		private bool $requireIdentity = false,
		private bool $requireEndpointIdentity = false,
		private array $iceServers = [],
		private string $iceUsername = "",
		private string $icePassword = "",
		private bool $relayOnly = false,
		private array $iceInterfaces = [],
		private array $advertisedAddresses = [],
		private ?array $udpPortRange = null,
		private ?string $tlsCertificatePath = null,
		private ?string $tlsKeyPath = null,
		private int $maxPendingNegotiations = 64,
		private int $maxNegotiationsPerAddress = 32,
		private bool $verboseLogging = false,
		private int $handshakeTimeout = NetherNetServer::DEFAULT_HANDSHAKE_TIMEOUT,
		private ?int $udpPort = null,
		private bool $udpPortRequired = true,
		private ?string $ipv6EndpointAddress = null
	){}


	public function getNetworkId() : int{
		return $this->networkId;
	}

	private function credentials() : ?Credentials{
		if($this->iceServers === []){
			return null;
		}
		return new Credentials([new IceServer(
			$this->iceServers,
			$this->iceUsername !== "" ? $this->iceUsername : null,
			$this->icePassword !== "" ? $this->icePassword : null
		)]);
	}

	public function make(\Logger $logger) : NetherNetServer{
		$server = new NetherNetServer(
			$logger,
			$this->networkId,
			new ServerData(
				serverName: $this->motd,
				protocol: ProtocolInfo::CURRENT_PROTOCOL,
				gameVersion: ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				levelName: $this->levelName,
				maxPlayerCount: $this->maxPlayerCount,
				acceptsOnlineAuth: $this->onlineMode,
				acceptsSelfSignedAuth: !$this->onlineMode // lol what
			),
			bindAddress: $this->bindAddress,
			port: $this->port,
			//the assertion is what binds the login chain on NetherNet, so it is held to the same
			//policy on the local network as by address unless the operator says otherwise
			requireIdentity: $this->requireIdentity,
			credentials: $this->credentials(),
			//the server list reaches a NetherNet server over HTTP on the server port, the same way
			//vanilla does it: the entry's MOTD comes from a GET and the join posts its offer there
			endpointAddress: "$this->bindAddress:$this->signallingPort",
			identityKeyPath: $this->identityKeyPath,
			identityDomain: $this->identityDomain,
			relayOnly: $this->relayOnly,
			//a player who joins by address is signed in and their client signs the offer, so the
			//assertion is the only thing binding that connection to the identity it logs in with
			requireEndpointIdentity: $this->requireEndpointIdentity,
			iceInterfaces: $this->iceInterfaces,
			advertisedAddresses: $this->advertisedAddresses,
			icePortRange: $this->udpPortRange,
			tlsCertificatePath: $this->tlsCertificatePath,
			tlsKeyPath: $this->tlsKeyPath,
			//a client that found the server on the local network does not sign its offer at all, so
			//only the players who joined by address can be held to a token the service issued - and
			//they are held to it exactly when they are held to carrying an assertion in the first place
			tokenTrust: TokenTrust::ANY,
			endpointTokenTrust: $this->requireEndpointIdentity ? TokenTrust::MINECRAFT_AUTH : TokenTrust::ANY,
			verboseLogging: $this->verboseLogging
		);
		$server->setNegotiationLimits($this->maxPendingNegotiations, $this->maxNegotiationsPerAddress);
		$server->setHandshakeTimeout($this->handshakeTimeout);
		$server->setUdpPort($this->udpPort, $this->udpPortRequired);
		if($this->ipv6EndpointAddress !== null){
			$server->addEndpointAddress($this->ipv6EndpointAddress);
		}
		return $server;
	}
}
