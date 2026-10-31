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

namespace pocketmine\stats;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use pocketmine\utils\Utils;
use pocketmine\VersionInfo;
use function count;
use function gzencode;
use function json_encode;
use function php_uname;
use function strlen;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const JSON_THROW_ON_ERROR;
use const PHP_VERSION;

/**
 * Sends anonymous server statistics to bStats.
 *
 * @see https://bstats.org/plugin/server-implementation/Altay/34140
 */
class BStatsMetricsTask extends AsyncTask{

	private const ENDPOINT = "https://bstats.org/api/v2/data/server-implementation";
	private const SERVICE_ID = 34140;
	private const METRICS_VERSION = "3.0.2";

	public string $data;

	public function __construct(Server $server){
		$version = VersionInfo::VERSION();

		$payload = [
			"serverUUID" => $server->getServerUniqueId()->toString(),
			"metricsVersion" => self::METRICS_VERSION,
			"osName" => php_uname("s"),
			"osArch" => php_uname("m"),
			"osVersion" => php_uname("r"),
			"coreCount" => Utils::getCoreCount(),
			"service" => [
				"id" => self::SERVICE_ID,
				"customCharts" => [
					self::lineChart("players", count($server->getOnlinePlayers())),
					self::pieChart("altayVersion", $version->getFullVersion(false)),
					self::pieChart("minecraftVersion", $server->getVersion()),
					self::pieChart("protocolVersion", (string) ProtocolInfo::CURRENT_PROTOCOL),
					self::pieChart("phpVersion", PHP_VERSION),
					self::pieChart("onlineMode", $server->getOnlineMode() ? "online" : "offline"),
					self::pieChart("pluginCount", (string) count($server->getPluginManager()->getPlugins()))
				]
			]
		];

		$this->data = json_encode($payload, JSON_THROW_ON_ERROR);
	}

	/**
	 * @phpstan-return array{chartId: string, data: array{value: int}}
	 */
	private static function lineChart(string $chartId, int $value) : array{
		return ["chartId" => $chartId, "data" => ["value" => $value]];
	}

	/**
	 * @phpstan-return array{chartId: string, data: array{value: string}}
	 */
	private static function pieChart(string $chartId, string $value) : array{
		return ["chartId" => $chartId, "data" => ["value" => $value]];
	}

	public function onRun() : void{
		$compressed = gzencode($this->data, 6);
		if($compressed === false){
			return;
		}

		try{
			Internet::simpleCurl(self::ENDPOINT, 5, [], [
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $compressed,
				CURLOPT_HTTPHEADER => [
					"Accept: application/json",
					"Connection: close",
					"Content-Encoding: gzip",
					"Content-Length: " . strlen($compressed),
					"Content-Type: application/json",
					"User-Agent: Metrics-Service/1"
				]
			]);
		}catch(InternetException){
			//bStats being unreachable is not worth bothering anyone about
		}
	}
}
