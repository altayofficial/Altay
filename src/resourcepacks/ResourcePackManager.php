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

namespace pocketmine\resourcepacks;

use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Path;
use function array_keys;
use function count;
use function file_exists;
use function filter_var;
use function gettype;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function mkdir;
use function parse_url;
use function rtrim;
use function sort;
use function strlen;
use function strtolower;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_URL;
use const PHP_URL_SCHEME;
use const SORT_STRING;

class ResourcePackManager{
	private const PACK_FILE_EXTENSIONS = ["zip", "mcpack"];

	private string $path;
	private bool $serverForceResources = false;

	/**
	 * @var ResourcePack[]
	 * @phpstan-var list<ResourcePack>
	 */
	private array $resourcePacks = [];

	/**
	 * @var ResourcePack[]
	 * @phpstan-var array<string, ResourcePack>
	 */
	private array $uuidList = [];

	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private array $encryptionKeys = [];

	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private array $cdnUrls = [];

	/**
	 * @param string $path    Path to resource-packs directory.
	 * @param mixed  $cdnUrls Map of pack file names to CDN URLs, from e.g. the "resource-packs.cdn-urls" zenith.yml property.
	 */
	public function __construct(string $path, \Logger $logger, bool $forceResources = false, mixed $cdnUrls = []){
		$this->path = $path;

		if(!file_exists($this->path)){
			$logger->debug("Resource packs path $path does not exist, creating directory");
			mkdir($this->path);
		}elseif(!is_dir($this->path)){
			throw new \InvalidArgumentException("Resource packs path $path exists and is not a directory");
		}

		$legacyResourcePacksYml = Path::join($this->path, "resource_packs.yml");
		if(file_exists($legacyResourcePacksYml)){
			$logger->warning("resource_packs/resource_packs.yml is deprecated; force_resources and cdn_urls have been merged into the \"resource-packs\" section of zenith.yml, and the resource stack is now detected automatically from the resource_packs directory. Move your settings there and delete the file to get rid of this warning. Using force_resources/cdn_urls from resource_packs.yml for now.");
			$legacyConfig = new Config($legacyResourcePacksYml, Config::YAML, []);
			$forceResources = (bool) $legacyConfig->get("force_resources", false);
			$cdnUrls = $legacyConfig->get("cdn_urls", []);
		}

		$this->serverForceResources = $forceResources;

		$logger->info("Loading resource packs...");

		$cdnUrlsConfig = $cdnUrls;
		if(!is_array($cdnUrlsConfig)){
			throw new \InvalidArgumentException("\"cdn-urls\" key should contain a map of pack names to URLs");
		}
		$configuredCdnUrls = [];
		foreach(Utils::promoteKeys($cdnUrlsConfig) as $packName => $cdnUrl){
			$packName = (string) $packName;
			if(!is_string($cdnUrl)){
				$logger->critical("Found invalid CDN URL for resource pack \"$packName\" of type " . gettype($cdnUrl));
				continue;
			}
			try{
				self::validateCdnUrl($cdnUrl);
				$configuredCdnUrls[$packName] = $cdnUrl;
			}catch(\InvalidArgumentException $e){
				$logger->critical("Found invalid CDN URL for resource pack \"$packName\": " . $e->getMessage());
			}
		}

		foreach($this->detectPackFiles() as $pack){
			try{
				$newPack = $this->loadPackFromPath(Path::join($this->path, $pack));

				$index = strtolower($newPack->getPackId());
				if(!Uuid::isValid($index)){
					//TODO: we should use Uuid in ResourcePack interface directly but that would break BC
					//for now we need to validate this here to make sure it doesn't cause crashes later on
					throw new ResourcePackException("Invalid UUID ($index)");
				}
				$this->uuidList[$index] = $newPack;
				$this->resourcePacks[] = $newPack;
				if(isset($configuredCdnUrls[$pack])){
					$this->cdnUrls[$index] = $configuredCdnUrls[$pack];
				}

				$keyPath = Path::join($this->path, $pack . ".key");
				if(file_exists($keyPath)){
					try{
						$key = Filesystem::fileGetContents($keyPath);
					}catch(\RuntimeException $e){
						throw new ResourcePackException("Could not read encryption key file: " . $e->getMessage(), 0, $e);
					}
					$key = rtrim($key, "\r\n");
					if(strlen($key) !== 32){
						throw new ResourcePackException("Invalid encryption key length, must be exactly 32 bytes");
					}
					$this->encryptionKeys[$index] = $key;
				}
			}catch(ResourcePackException $e){
				$logger->critical("Could not load resource pack \"$pack\": " . $e->getMessage());
			}
		}

		$logger->debug("Successfully loaded " . count($this->resourcePacks) . " resource packs");
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	private function detectPackFiles() : array{
		$packs = [];
		foreach(new \DirectoryIterator($this->path) as $info){
			if($info->isDot() || !$info->isFile()){
				continue;
			}
			if(in_array(strtolower($info->getExtension()), self::PACK_FILE_EXTENSIONS, true)){
				$packs[] = $info->getFilename();
			}
		}
		sort($packs, SORT_STRING);

		return $packs;
	}

	private function loadPackFromPath(string $packPath) : ResourcePack{
		if(!file_exists($packPath)){
			throw new ResourcePackException("File or directory not found");
		}
		if(is_dir($packPath)){
			throw new ResourcePackException("Directory resource packs are unsupported");
		}

		//Detect the type of resource pack.
		$info = new \SplFileInfo($packPath);
		if(in_array(strtolower($info->getExtension()), self::PACK_FILE_EXTENSIONS, true)){
			return new ZippedResourcePack($packPath);
		}

		throw new ResourcePackException("Format not recognized");
	}

	private static function validateCdnUrl(string $url) : void{
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if(!is_string($scheme) || (strtolower($scheme) !== "http" && strtolower($scheme) !== "https") || filter_var($url, FILTER_VALIDATE_URL) === false){
			throw new \InvalidArgumentException("URL must be a valid HTTP or HTTPS URL");
		}
	}

	/**
	 * Returns the directory which resource packs are loaded from.
	 */
	public function getPath() : string{
		return $this->path . DIRECTORY_SEPARATOR;
	}

	/**
	 * Returns whether players must accept resource packs in order to join.
	 */
	public function resourcePacksRequired() : bool{
		return $this->serverForceResources;
	}

	/**
	 * Sets whether players must accept resource packs in order to join.
	 */
	public function setResourcePacksRequired(bool $value) : void{
		$this->serverForceResources = $value;
	}

	/**
	 * Returns an array of resource packs in use, sorted in order of priority.
	 * @return ResourcePack[]
	 * @phpstan-return list<ResourcePack>
	 */
	public function getResourceStack() : array{
		return $this->resourcePacks;
	}

	/**
	 * Sets the resource packs to use. Packs earliest in the list will appear at the top of the stack (maximum
	 * priority), and later ones will appear below (lower priority), in the same manner as the Bedrock resource packs
	 * screen in-game.
	 *
	 * @param ResourcePack[] $resourceStack
	 * @phpstan-param list<ResourcePack> $resourceStack
	 */
	public function setResourceStack(array $resourceStack) : void{
		$uuidList = [];
		$resourcePacks = [];
		foreach($resourceStack as $pack){
			$uuid = strtolower($pack->getPackId());
			if(!Uuid::isValid($uuid)){
				//TODO: we should use Uuid in ResourcePack interface directly but that would break BC
				//for now we need to validate this here to make sure it doesn't cause crashes later on
				throw new \InvalidArgumentException("Invalid resource pack UUID ($uuid)");
			}
			if(isset($uuidList[$uuid])){
				throw new \InvalidArgumentException("Cannot load two resource pack with the same UUID ($uuid)");
			}
			$uuidList[$uuid] = $pack;
			$resourcePacks[] = $pack;
		}
		$this->resourcePacks = $resourcePacks;
		$this->uuidList = $uuidList;
	}

	/**
	 * Returns the resource pack matching the specified UUID string, or null if the ID was not recognized.
	 */
	public function getPackById(string $id) : ?ResourcePack{
		return $this->uuidList[strtolower($id)] ?? null;
	}

	/**
	 * Returns an array of pack IDs for packs currently in use.
	 * @return string[]
	 */
	public function getPackIdList() : array{
		return array_keys($this->uuidList);
	}

	/**
	 * Returns the key with which the pack was encrypted, or null if the pack has no key.
	 */
	public function getPackEncryptionKey(string $id) : ?string{
		return $this->encryptionKeys[strtolower($id)] ?? null;
	}

	/**
	 * Returns the CDN URL for the specified resource pack, or null if the pack should be sent by the server.
	 */
	public function getPackCdnUrl(string $id) : ?string{
		return $this->cdnUrls[strtolower($id)] ?? null;
	}

	/**
	 * Sets the encryption key to use for decrypting the specified resource pack. The pack will **NOT** be decrypted by
	 * PocketMine-MP; the key is simply passed to the client to allow it to decrypt the pack after downloading it.
	 */
	public function setPackEncryptionKey(string $id, ?string $key) : void{
		$id = strtolower($id);
		if($key === null){
			//allow deprovisioning keys for resource packs that have been removed
			unset($this->encryptionKeys[$id]);
		}elseif(isset($this->uuidList[$id])){
			if(strlen($key) !== 32){
				throw new \InvalidArgumentException("Encryption key must be exactly 32 bytes long");
			}
			$this->encryptionKeys[$id] = $key;
		}else{
			throw new \InvalidArgumentException("Unknown pack ID $id");
		}
	}

	/**
	 * Sets the CDN URL clients may use to download the specified resource pack.
	 */
	public function setPackCdnUrl(string $id, ?string $url) : void{
		$id = strtolower($id);
		if($url === null){
			unset($this->cdnUrls[$id]);
		}elseif(isset($this->uuidList[$id])){
			self::validateCdnUrl($url);
			$this->cdnUrls[$id] = $url;
		}else{
			throw new \InvalidArgumentException("Unknown pack ID $id");
		}
	}
}
