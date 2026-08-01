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

namespace pocketmine\network\mcpe\transport;

use altay\network\nethernet\NetherNetTransport;
use altay\network\nethernet\ServerData;
use altay\network\transport\Transport;
use function dirname;
use function file_exists;
use function getenv;
use function putenv;
use const PHP_BINARY;

final class NetherNetTransportFactory implements TransportFactory{

	public function __construct(
		private int $networkId,
		private string $motd,
		private string $levelName,
		private int $maxPlayerCount,
		private string $bindAddress = "0.0.0.0",
		private int $port = NetherNetTransport::DISCOVERY_PORT,
		private bool $onlineMode = false
	){}

	public function getName() : string{
		return "nethernet";
	}

	public function getNetworkId() : int{
		return $this->networkId;
	}

	public function make(\Logger $logger) : Transport{
		self::exportNativeLibraryPaths();
		return new NetherNetTransport(
			$logger,
			$this->networkId,
			new ServerData(
				serverName: $this->motd,
				levelName: $this->levelName,
				maxPlayerCount: $this->maxPlayerCount,
				acceptsOnlineAuth: $this->onlineMode,
				acceptsSelfSignedAuth: !$this->onlineMode // lol what
			),
			$this->bindAddress,
			$this->port,
			//vanilla clients do not attach identity assertions to LAN offers, they only do so
			//for Xbox Live session signaling so that means assertions are still verified when present
			false
		);
	}

	private static function exportNativeLibraryPaths() : void{
		$libDir = dirname(PHP_BINARY, 2) . "/lib";
		foreach([
			"LIBSSL_PATH" => "libssl.so",
			"LIB_SRTP_PATH" => "libsrtp2.so",
			"LIB_OPUS_PATH" => "libopus.so",
			"LIBVPX_PATH" => "libvpx.so"
		] as $env => $file){
			if(getenv($env) === false && file_exists("$libDir/$file")){
				putenv("$env=$libDir/$file");
			}
		}
	}
}
