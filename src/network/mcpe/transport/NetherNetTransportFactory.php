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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function dirname;
use function file_exists;
use function getenv;
use function putenv;
use const PHP_BINARY;
use const PHP_OS_FAMILY;

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
				protocol: ProtocolInfo::CURRENT_PROTOCOL,
				gameVersion: ProtocolInfo::MINECRAFT_VERSION_NETWORK,
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
		//MacOS builds ship .dylib instead of .so
		$extensions = PHP_OS_FAMILY === "Darwin" ? ["dylib", "so"] : ["so"];
		foreach([
			"LIBSSL_PATH" => "libssl",
			"LIB_SRTP_PATH" => "libsrtp2",
			"LIB_OPUS_PATH" => "libopus",
			"LIBVPX_PATH" => "libvpx"
		] as $env => $name){
			if(getenv($env) !== false){
				continue;
			}
			foreach($extensions as $extension){
				$path = "$libDir/$name.$extension";
				if(file_exists($path)){
					putenv("$env=$path");
					break;
				}
			}
		}
	}
}
