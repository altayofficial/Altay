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

/**
 * Set-up wizard used on the first run
 * Can be disabled with --no-wizard
 */
namespace pocketmine\wizard;

use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Language;
use pocketmine\lang\LanguageNotFoundException;
use pocketmine\lang\Translatable;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use pocketmine\utils\Utils;
use pocketmine\VersionInfo;
use Symfony\Component\Filesystem\Path;
use function fgets;
use function file_put_contents;
use function is_bool;
use function is_int;
use function preg_quote;
use function preg_replace_callback;
use function sleep;
use function str_replace;
use function strtolower;
use function trim;
use const PHP_EOL;
use const STDIN;

class SetupWizard{
	/** @deprecated */
	public const DEFAULT_NAME = Server::DEFAULT_SERVER_NAME;
	/** @deprecated */
	public const DEFAULT_PORT = Server::DEFAULT_PORT_IPV4;
	/** @deprecated */
	public const DEFAULT_PLAYERS = Server::DEFAULT_MAX_PLAYERS;

	private Language $lang;

	/**
	 * Chosen values for the "server" section of zenith.yml, keyed by leaf property name (e.g. "motd").
	 * @var array<string, string|int|bool>
	 */
	private array $serverProperties = [];

	public function __construct(
		private string $dataPath
	){}

	public function run() : bool{
		$this->message(VersionInfo::NAME . " set-up wizard");

		try{
			$langs = Language::getLanguageList();
		}catch(LanguageNotFoundException $e){
			$this->error("No language files found, please use provided builds or clone the repository recursively.");
			return false;
		}

		$this->message("Please select a language");
		foreach(Utils::stringifyKeys($langs) as $short => $native){
			$this->writeLine(" $native => $short");
		}

		do{
			$lang = strtolower($this->getInput("Language", "eng"));
			if(!isset($langs[$lang])){
				$this->error("Couldn't find the language");
				$lang = null;
			}
		}while($lang === null);

		$this->lang = new Language($lang);

		$this->message($this->lang->translate(KnownTranslationFactory::language_has_been_selected()));

		if(!$this->showLicense()){
			return false;
		}

		//This has to happen here to prevent user avoiding agreeing to license
		$this->serverProperties["language"] = $lang;
		$this->writeServerConfig();

		if(strtolower($this->getInput($this->lang->translate(KnownTranslationFactory::skip_installer()), "n", "y/N")) === "y"){
			$this->printIpDetails();
			return true;
		}

		$this->writeLine();
		$this->welcome();

		$this->generateBaseConfig();
		$this->generateUserFiles();
		$this->networkFunctions();
		$this->writeServerConfig();

		$this->printIpDetails();

		$this->endWizard();

		return true;
	}

	private function writeServerConfig() : void{
		$content = Filesystem::fileGetContents(Path::join(\pocketmine\RESOURCE_PATH, "zenith.yml"));
		if(VersionInfo::IS_DEVELOPMENT_BUILD){
			$content = str_replace("preferred-channel: stable", "preferred-channel: beta", $content);
		}
		foreach(Utils::stringifyKeys($this->serverProperties) as $key => $value){
			$content = $this->setYamlValue($content, $key, $value);
		}
		file_put_contents(Path::join($this->dataPath, "zenith.yml"), $content);
	}

	private function setYamlValue(string $content, string $key, string|int|bool $value) : string{
		if(is_bool($value)){
			$encoded = $value ? "true" : "false";
		}elseif(is_int($value)){
			$encoded = (string) $value;
		}else{
			$encoded = '"' . str_replace(["\\", "\""], ["\\\\", "\\\""], $value) . '"';
		}
		$pattern = '/^(  ' . preg_quote($key, '/') . ': ).*$/m';
		return preg_replace_callback($pattern, fn(array $matches) => $matches[1] . $encoded, $content, 1) ?? $content;
	}

	private function showLicense() : bool{
		$this->message($this->lang->translate(KnownTranslationFactory::welcome_to_pocketmine(VersionInfo::NAME)));
		echo <<<LICENSE

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

LICENSE;
		$this->writeLine();
		if(strtolower($this->getInput($this->lang->translate(KnownTranslationFactory::accept_license()), "n", "y/N")) !== "y"){
			$this->error($this->lang->translate(KnownTranslationFactory::you_have_to_accept_the_license(VersionInfo::NAME)));
			sleep(5);

			return false;
		}

		return true;
	}

	private function welcome() : void{
		$this->message($this->lang->translate(KnownTranslationFactory::setting_up_server_now()));
		$this->message($this->lang->translate(KnownTranslationFactory::default_values_info()));
		$this->message($this->lang->translate(KnownTranslationFactory::server_properties()));
	}

	private function askPort(Translatable $prompt, int $default) : int{
		while(true){
			$port = (int) $this->getInput($this->lang->translate($prompt), (string) $default);
			if($port <= 0 || $port > 65535){
				$this->error($this->lang->translate(KnownTranslationFactory::invalid_port()));
				continue;
			}

			return $port;
		}
	}

	private function generateBaseConfig() : void{
		$this->serverProperties["motd"] = $this->getInput($this->lang->translate(KnownTranslationFactory::name_your_server()), Server::DEFAULT_SERVER_NAME);

		$this->message($this->lang->translate(KnownTranslationFactory::port_warning()));

		$this->serverProperties["server-port"] = $this->askPort(KnownTranslationFactory::server_port_v4(), Server::DEFAULT_PORT_IPV4);
		$this->serverProperties["server-portv6"] = $this->askPort(KnownTranslationFactory::server_port_v6(), Server::DEFAULT_PORT_IPV6);

		$this->message($this->lang->translate(KnownTranslationFactory::gamemode_info()));

		do{
			$input = (int) $this->getInput($this->lang->translate(KnownTranslationFactory::default_gamemode()), "0");
			$gamemode = match($input){
				0 => GameMode::SURVIVAL,
				1 => GameMode::CREATIVE,
				default => null
			};
		}while($gamemode === null);
		$this->serverProperties["gamemode"] = strtolower($gamemode->name);

		$this->serverProperties["max-players"] = (int) $this->getInput($this->lang->translate(KnownTranslationFactory::max_players()), (string) Server::DEFAULT_MAX_PLAYERS);

		$this->serverProperties["view-distance"] = (int) $this->getInput($this->lang->translate(KnownTranslationFactory::view_distance()), (string) Server::DEFAULT_MAX_VIEW_DISTANCE);
	}

	private function generateUserFiles() : void{
		$this->message($this->lang->translate(KnownTranslationFactory::op_info()));

		$op = strtolower($this->getInput($this->lang->translate(KnownTranslationFactory::op_who()), ""));
		if($op === ""){
			$this->error($this->lang->translate(KnownTranslationFactory::op_warning()));
		}else{
			$ops = new Config(Path::join($this->dataPath, "ops.txt"), Config::ENUM);
			$ops->set($op, true);
			$ops->save();
		}

		$this->message($this->lang->translate(KnownTranslationFactory::whitelist_info()));

		if(strtolower($this->getInput($this->lang->translate(KnownTranslationFactory::whitelist_enable()), "n", "y/N")) === "y"){
			$this->error($this->lang->translate(KnownTranslationFactory::whitelist_warning()));
			$this->serverProperties["white-list"] = true;
		}else{
			$this->serverProperties["white-list"] = false;
		}
	}

	private function networkFunctions() : void{
		$this->error($this->lang->translate(KnownTranslationFactory::query_warning1()));
		$this->error($this->lang->translate(KnownTranslationFactory::query_warning2()));
		$this->serverProperties["enable-query"] = strtolower($this->getInput($this->lang->translate(KnownTranslationFactory::query_disable()), "n", "y/N")) !== "y";
	}

	private function printIpDetails() : void{
		$this->message($this->lang->translate(KnownTranslationFactory::ip_get()));

		$externalIP = Internet::getIP();
		if($externalIP === false){
			$externalIP = "unknown (server offline)";
		}
		try{
			$internalIP = Internet::getInternalIP();
		}catch(InternetException $e){
			$internalIP = "unknown (" . $e->getMessage() . ")";
		}

		$this->error($this->lang->translate(KnownTranslationFactory::ip_warning($externalIP, $internalIP)));
		$this->error($this->lang->translate(KnownTranslationFactory::ip_confirm()));
		$this->readLine();
	}

	private function endWizard() : void{
		$this->message($this->lang->translate(KnownTranslationFactory::you_have_finished()));
		$this->message($this->lang->translate(KnownTranslationFactory::pocketmine_plugins()));
		$this->message($this->lang->translate(KnownTranslationFactory::pocketmine_will_start(VersionInfo::NAME)));

		$this->writeLine();
		$this->writeLine();

		sleep(4);
	}

	private function writeLine(string $line = "") : void{
		echo $line . PHP_EOL;
	}

	private function readLine() : string{
		return trim((string) fgets(STDIN));
	}

	private function message(string $message) : void{
		$this->writeLine("[*] " . $message);
	}

	private function error(string $message) : void{
		$this->writeLine("[!] " . $message);
	}

	private function getInput(string $message, string $default = "", string $options = "") : string{
		$message = "[?] " . $message;

		if($options !== "" || $default !== ""){
			$message .= " (" . ($options === "" ? $default : $options) . ")";
		}
		$message .= ": ";

		echo $message;

		$input = $this->readLine();

		return $input === "" ? $default : $input;
	}
}
