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

use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\utils\YamlEditor;
use Symfony\Component\Filesystem\Path;
use function array_is_list;
use function array_key_exists;
use function explode;
use function file_exists;
use function is_array;
use function rename;
use function str_replace;
use function yaml_parse;
use const PHP_INT_MAX;

/**
 * Builds altay.yml on first startup. Servers set up before it existed had their settings spread over
 * server.properties, pocketmine.yml, plugin_list.yml and resource_packs/resource_packs.yml, so whatever is found in
 * those gets carried over.
 *
 * @internal
 */
final class LegacyConfigMigrator{
	public const ALTAY_YML = "altay.yml";

	private const SERVER_PROPERTIES = "server.properties";
	private const POCKETMINE_YML = "pocketmine.yml";
	private const PLUGIN_LIST_YML = "plugin_list.yml";
	private const RESOURCE_PACKS_YML = "resource_packs/resource_packs.yml";

	private const PLUGIN_LIST_PATHS = [
		"mode" => "plugins.list-mode",
		"plugins" => "plugins.list",
	];
	private const RESOURCE_PACKS_PATHS = [
		"force_resources" => "resource-packs.force-resources",
		"cdn_urls" => "resource-packs.cdn-urls",
		"resource_stack" => "resource-packs.resource-stack",
	];

	private function __construct(){
		//NOOP
	}

	/**
	 * Returns the old config files that exist in the data folder, relative to it.
	 *
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public static function findLegacyFiles(string $dataPath) : array{
		$result = [];
		foreach([self::SERVER_PROPERTIES, self::POCKETMINE_YML, self::PLUGIN_LIST_YML, self::RESOURCE_PACKS_YML] as $file){
			if(file_exists(Path::join($dataPath, $file))){
				$result[] = $file;
			}
		}
		return $result;
	}

	/**
	 * Returns the content for a new altay.yml: the bundled default with any settings from the old files on top.
	 * The comments from the default file are kept.
	 *
	 * @throws \RuntimeException if one of the old files can't be read
	 */
	public static function createConfig(string $dataPath) : string{
		$content = Filesystem::fileGetContents(Path::join(\pocketmine\RESOURCE_PATH, self::ALTAY_YML));
		if(VersionInfo::IS_DEVELOPMENT_BUILD){
			$content = str_replace("preferred-channel: stable", "preferred-channel: beta", $content);
		}
		$defaults = ErrorToExceptionHandler::trap(fn() => yaml_parse($content));
		if(!is_array($defaults)){
			throw new AssumptionFailedError("Bundled " . self::ALTAY_YML . " should be a map");
		}
		$editor = new YamlEditor($content);

		//pocketmine.yml first, server.properties used to win over it for the language
		$pocketmineYml = Path::join($dataPath, self::POCKETMINE_YML);
		if(file_exists($pocketmineYml)){
			self::merge($editor, [], $defaults, (new Config($pocketmineYml, Config::YAML))->getAll());
		}

		$serverProperties = Path::join($dataPath, self::SERVER_PROPERTIES);
		if(file_exists($serverProperties)){
			foreach(Utils::promoteKeys(Config::parseProperties(Filesystem::fileGetContents($serverProperties))) as $key => $value){
				self::setIfChanged($editor, $defaults, ServerProperties::toPath((string) $key), $value);
			}
		}

		foreach([
			self::PLUGIN_LIST_YML => self::PLUGIN_LIST_PATHS,
			self::RESOURCE_PACKS_YML => self::RESOURCE_PACKS_PATHS
		] as $file => $paths){
			$fullPath = Path::join($dataPath, $file);
			if(!file_exists($fullPath)){
				continue;
			}
			$old = (new Config($fullPath, Config::YAML))->getAll();
			foreach($paths as $oldKey => $path){
				if(array_key_exists($oldKey, $old)){
					self::setIfChanged($editor, $defaults, $path, $old[$oldKey]);
				}
			}
		}

		return $editor->getText();
	}

	/**
	 * Renames the old config files to *.old, so they're out of the way without losing anything.
	 */
	public static function retireLegacyFiles(string $dataPath) : void{
		foreach(self::findLegacyFiles($dataPath) as $file){
			$fullPath = Path::join($dataPath, $file);
			rename($fullPath, $fullPath . ".old");
		}
	}

	/**
	 * Copies the old pocketmine.yml over the default one key by key, instead of replacing whole sections, so that
	 * settings the old file doesn't have keep their default and comments.
	 *
	 * @param string[] $path
	 * @param mixed[]  $defaults
	 * @param mixed[]  $old
	 * @phpstan-param list<string> $path
	 */
	private static function merge(YamlEditor $editor, array $path, ?array $defaults, array $old) : void{
		foreach(Utils::promoteKeys($old) as $key => $value){
			$key = (string) $key;
			$keyPath = [...$path, $key];
			$default = $defaults !== null && array_key_exists($key, $defaults) ? $defaults[$key] : null;

			if(is_array($value) && $value !== [] && !array_is_list($value) && ($default === null || (is_array($default) && !array_is_list($default)))){
				self::merge($editor, $keyPath, $default, $value);
			}elseif($value !== $default){
				$editor->set($keyPath, $value);
			}
		}
	}

	/**
	 * @param mixed[] $defaults
	 */
	private static function setIfChanged(YamlEditor $editor, array $defaults, string $path, mixed $value) : void{
		$default = $defaults;
		foreach(explode(".", $path, limit: PHP_INT_MAX) as $part){
			if(!is_array($default) || !array_key_exists($part, $default)){
				$default = null;
				break;
			}
			$default = $default[$part];
		}
		if($value !== $default){
			$editor->set(explode(".", $path, limit: PHP_INT_MAX), $value);
		}
	}
}
