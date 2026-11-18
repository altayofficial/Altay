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
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\utils\YamlEditor;
use function array_key_exists;
use function count;
use function explode;
use function getopt;
use function is_bool;
use function is_int;
use function is_string;
use function strtolower;
use function yaml_parse;
use const PHP_INT_MAX;

final class ServerConfigGroup{
	/**
	 * @var mixed[]
	 * @phpstan-var array<string, mixed>
	 */
	private array $propertyCache = [];

	/**
	 * Paths written through setConfig*() since the last save.
	 * @var true[]
	 * @phpstan-var array<string, true>
	 */
	private array $changedPaths = [];

	public function __construct(
		private Config $altayYml
	){}

	public function getProperty(string $variable, mixed $defaultValue = null) : mixed{
		if(!array_key_exists($variable, $this->propertyCache)){
			$v = getopt("", ["$variable::"]);
			if(isset($v[$variable])){
				$this->propertyCache[$variable] = $v[$variable];
			}else{
				$this->propertyCache[$variable] = $this->altayYml->getNested($variable);
			}
		}

		return $this->propertyCache[$variable] ?? $defaultValue;
	}

	public function getPropertyBool(string $variable, bool $defaultValue) : bool{
		return (bool) $this->getProperty($variable, $defaultValue);
	}

	public function getPropertyInt(string $variable, int $defaultValue) : int{
		return (int) $this->getProperty($variable, $defaultValue);
	}

	public function getPropertyString(string $variable, string $defaultValue) : string{
		return (string) $this->getProperty($variable, $defaultValue);
	}

	/**
	 * The getConfig*() family takes the old server.properties key names (see {@link ServerProperties}), which are
	 * still what --key=value command line overrides use for these settings.
	 */
	private function getConfigValue(string $variable, mixed $defaultValue) : mixed{
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			return $v[$variable];
		}

		return $this->altayYml->getNested(ServerProperties::toPath($variable)) ?? $defaultValue;
	}

	private function setConfigValue(string $variable, mixed $value) : void{
		$path = ServerProperties::toPath($variable);
		$this->altayYml->setNested($path, $value);
		$this->changedPaths[$path] = true;
		//getProperty() may have cached this path, or one of its parents
		$this->propertyCache = [];
	}

	public function getConfigString(string $variable, string $defaultValue = "") : string{
		return (string) $this->getConfigValue($variable, $defaultValue);
	}

	public function setConfigString(string $variable, string $value) : void{
		$this->setConfigValue($variable, $value);
	}

	public function getConfigInt(string $variable, int $defaultValue = 0) : int{
		return (int) $this->getConfigValue($variable, $defaultValue);
	}

	public function setConfigInt(string $variable, int $value) : void{
		$this->setConfigValue($variable, $value);
	}

	public function getConfigBool(string $variable, bool $defaultValue = false) : bool{
		$value = $this->getConfigValue($variable, $defaultValue);
		if(is_bool($value)){
			return $value;
		}
		if(is_int($value)){
			return $value !== 0;
		}
		if(is_string($value)){
			switch(strtolower($value)){
				case "on":
				case "true":
				case "1":
				case "yes":
					return true;
			}
		}

		return false;
	}

	public function setConfigBool(string $variable, bool $value) : void{
		$this->setConfigValue($variable, $value);
	}

	public function save() : void{
		if(count($this->changedPaths) === 0){
			return;
		}
		$file = $this->altayYml->getPath();
		try{
			$editor = new YamlEditor(Filesystem::fileGetContents($file));
			foreach(Utils::stringifyKeys($this->changedPaths) as $path => $_){
				$editor->set(explode(".", $path, limit: PHP_INT_MAX), $this->altayYml->getNested($path));
			}
			$content = $editor->getText();

			//the editor only handles plain block maps, so don't trust it blindly with a hand edited file
			$parsed = ErrorToExceptionHandler::trap(fn() => yaml_parse(Config::fixYAMLIndexes($content)));
			if($parsed !== $this->altayYml->getAll()){
				throw new \InvalidArgumentException("Edited config doesn't match what's in memory");
			}
			Filesystem::safeFilePutContents($file, $content);
			$this->altayYml->setChanged(false);
		}catch(\InvalidArgumentException|\RuntimeException|\ErrorException){
			//losing the comments is a shame, but losing the change would be worse
			$this->altayYml->save();
		}
		$this->changedPaths = [];
	}
}
