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

namespace pocketmine;

/**
 * @internal
 * Constants for all core server properties. These used to live in server.properties, but have since been merged into
 * the "server" section of zenith.yml. The values are nested keys ("server.<name>") used with ServerConfigGroup.
 */
final class ServerProperties{

	private function __construct(){
		//NOOP
	}

	public const AUTO_SAVE = "server.auto-save";
	public const DEFAULT_WORLD_GENERATOR = "server.level-type";
	public const DEFAULT_WORLD_GENERATOR_SETTINGS = "server.generator-settings";
	public const DEFAULT_WORLD_NAME = "server.level-name";
	public const DEFAULT_WORLD_SEED = "server.level-seed";
	public const DIFFICULTY = "server.difficulty";
	public const ENABLE_IPV6 = "server.enable-ipv6";
	public const ENABLE_QUERY = "server.enable-query";
	public const FORCE_GAME_MODE = "server.force-gamemode";
	public const GAME_MODE = "server.gamemode";
	public const HARDCORE = "server.hardcore";
	public const LANGUAGE = "server.language";
	public const MAX_PLAYERS = "server.max-players";
	public const MOTD = "server.motd";
	public const PVP = "server.pvp";
	public const SERVER_IPV4 = "server.server-ip";
	public const SERVER_IPV6 = "server.server-ipv6";
	public const SERVER_PORT_IPV4 = "server.server-port";
	public const SERVER_PORT_IPV6 = "server.server-portv6";
	public const SUB_MOTD = "server.sub-motd";
	public const VIEW_DISTANCE = "server.view-distance";
	public const WHITELIST = "server.white-list";
	public const XBOX_AUTH = "server.xbox-auth";
}
