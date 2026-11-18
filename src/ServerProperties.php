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

namespace pocketmine;

/**
 * @internal
 * Names of the settings that used to live in server.properties. They're still accepted by the config group's
 * getConfig*() and setConfig*() methods and as command line overrides, see PATHS for where they live in altay.yml.
 */
final class ServerProperties{

	private function __construct(){
		//NOOP
	}

	public const AUTO_SAVE = "auto-save";
	public const DEFAULT_WORLD_GENERATOR = "level-type";
	public const DEFAULT_WORLD_GENERATOR_SETTINGS = "generator-settings";
	public const DEFAULT_WORLD_NAME = "level-name";
	public const DEFAULT_WORLD_SEED = "level-seed";
	public const DIFFICULTY = "difficulty";
	public const ENABLE_IPV6 = "enable-ipv6";
	public const ENABLE_QUERY = "enable-query";
	public const FORCE_GAME_MODE = "force-gamemode";
	public const GAME_MODE = "gamemode";
	public const HARDCORE = "hardcore";
	public const LANGUAGE = "language";
	public const MAX_PLAYERS = "max-players";
	public const MOTD = "motd";
	public const SUB_MOTD = "sub-motd";
	public const PVP = "pvp";
	public const SERVER_IPV4 = "server-ip";
	public const SERVER_IPV6 = "server-ipv6";
	public const SERVER_PORT_IPV4 = "server-port";
	public const SERVER_PORT_IPV6 = "server-portv6";
	public const VIEW_DISTANCE = "view-distance";
	public const WHITELIST = "white-list";
	public const XBOX_AUTH = "xbox-auth";

	/**
	 * Where each of the keys above lives in altay.yml. Keys missing here (plugins used to put their own stuff in
	 * server.properties) go under the server section.
	 */
	public const PATHS = [
		self::AUTO_SAVE => "level-settings.auto-save",
		self::DEFAULT_WORLD_GENERATOR => "level-settings.default-world.generator",
		self::DEFAULT_WORLD_GENERATOR_SETTINGS => "level-settings.default-world.generator-settings",
		self::DEFAULT_WORLD_NAME => "level-settings.default-world.name",
		self::DEFAULT_WORLD_SEED => "level-settings.default-world.seed",
		self::DIFFICULTY => "player.difficulty",
		self::ENABLE_IPV6 => "server.ipv6.enabled",
		self::ENABLE_QUERY => "server.enable-query",
		self::FORCE_GAME_MODE => "player.force-gamemode",
		self::GAME_MODE => "player.gamemode",
		self::HARDCORE => "player.hardcore",
		self::LANGUAGE => "settings.language",
		self::MAX_PLAYERS => "player.max-players",
		self::MOTD => "server.motd",
		self::SUB_MOTD => "server.sub-motd",
		self::PVP => "player.pvp",
		self::SERVER_IPV4 => "server.ip",
		self::SERVER_IPV6 => "server.ipv6.ip",
		self::SERVER_PORT_IPV4 => "server.port",
		self::SERVER_PORT_IPV6 => "server.ipv6.port",
		self::VIEW_DISTANCE => "player.view-distance",
		self::WHITELIST => "player.white-list",
		self::XBOX_AUTH => "server.xbox-auth",
	];

	public static function toPath(string $key) : string{
		return self::PATHS[$key] ?? "server." . $key;
	}
}
