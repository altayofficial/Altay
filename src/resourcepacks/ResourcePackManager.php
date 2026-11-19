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

namespace pocketmine\resourcepacks;

use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Filesystem\Path;
use function array_map;
use function copy;
use function count;
use function file_exists;
use function filter_var;
use function gettype;
use function is_array;
use function is_dir;
use function is_float;
use function is_int;
use function is_string;
use function mkdir;
use function parse_url;
use function rtrim;
use function strlen;
use function strtolower;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_URL;
use const PHP_URL_SCHEME;

class ResourcePackManager{
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
	 * @param string $path Path to resource-packs directory.
	 */
	public function __construct(string $path, \Logger $logger){
		$this->path = $path;

		if(!file_exists($this->path)){
			$logger->debug("Resource packs path $path does not exist, creating directory");
			mkdir($this->path);
		}elseif(!is_dir($this->path)){
			throw new \InvalidArgumentException("Resource packs path $path exists and is not a directory");
		}

		$resourcePacksYml = Path::join($this->path, "resource_packs.yml");
		if(!file_exists($resourcePacksYml)){
			copy(Path::join(\pocketmine\RESOURCE_PATH, "resource_packs.yml"), $resourcePacksYml);
		}

		$resourcePacksConfig = new Config($resourcePacksYml, Config::YAML, []);

		$this->serverForceResources = (bool) $resourcePacksConfig->get("force_resources", false);

		$logger->info("Loading resource packs...");

		$resourceStack = $resourcePacksConfig->get("resource_stack", []);
		if(!is_array($resourceStack)){
			throw new \InvalidArgumentException("\"resource_stack\" key should contain a list of pack names");
		}

		$cdnUrlsConfig = $resourcePacksConfig->get("cdn_urls", []);
		if(!is_array($cdnUrlsConfig)){
			throw new \InvalidArgumentException("\"cdn_urls\" key should contain a map of pack names to URLs");
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

		foreach(Utils::promoteKeys($resourceStack) as $pos => $pack){
			if(!is_string($pack) && !is_int($pack) && !is_float($pack)){
				$logger->critical("Found invalid entry in resource pack list at offset $pos of type " . gettype($pack));
				continue;
			}
			$pack = (string) $pack;
			try{
				$newPack = $this->loadPackFromPath(Path::join($this->path, $pack));

				$printableId = $newPack->getPackId()->toString();
				$this->uuidList[$printableId] = $newPack;
				$this->resourcePacks[] = $newPack;
				if(isset($configuredCdnUrls[$pack])){
					$this->cdnUrls[$printableId] = $configuredCdnUrls[$pack];
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
					$this->encryptionKeys[$printableId] = $key;
				}
			}catch(ResourcePackException $e){
				$logger->critical("Could not load resource pack \"$pack\": " . $e->getMessage());
			}
		}

		$logger->debug("Successfully loaded " . count($this->resourcePacks) . " resource packs");
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
		switch($info->getExtension()){
			case "zip":
			case "mcpack":
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
			$printableId = $pack->getPackId()->toString();
			if(isset($uuidList[$printableId])){
				throw new \InvalidArgumentException("Cannot load two resource pack with the same UUID ($printableId)");
			}
			$uuidList[$printableId] = $pack;
			$resourcePacks[] = $pack;
		}
		$this->resourcePacks = $resourcePacks;
		$this->uuidList = $uuidList;
	}

	/**
	 * Returns the resource pack matching the specified UUID string, or null if the ID was not recognized.
	 */
	public function getPackById(UuidInterface|string $id) : ?ResourcePack{
		return $this->uuidList[$id instanceof UuidInterface ? $id->toString() : strtolower($id)] ?? null;
	}

	/**
	 * Returns an array of pack IDs for packs currently in use.
	 * @return UuidInterface[]
	 */
	public function getPackIdList() : array{
		return array_map(fn(ResourcePack $p) => $p->getPackId(), $this->uuidList);
	}

	/**
	 * Returns the key with which the pack was encrypted, or null if the pack has no key.
	 */
	public function getPackEncryptionKey(UuidInterface $id) : ?string{
		return $this->encryptionKeys[$id->toString()] ?? null;
	}

	/**
	 * Returns the CDN URL for the specified resource pack, or null if the pack should be sent by the server.
	 */
	public function getPackCdnUrl(UuidInterface $id) : ?string{
		return $this->cdnUrls[$id->toString()] ?? null;
	}

	/**
	 * Sets the encryption key to use for decrypting the specified resource pack. The pack will **NOT** be decrypted by
	 * PocketMine-MP; the key is simply passed to the client to allow it to decrypt the pack after downloading it.
	 */
	public function setPackEncryptionKey(UuidInterface $id, ?string $key) : void{
		$printableId = $id->toString();
		if($key === null){
			//allow deprovisioning keys for resource packs that have been removed
			unset($this->encryptionKeys[$printableId]);
		}elseif(isset($this->uuidList[$printableId])){
			if(strlen($key) !== 32){
				throw new \InvalidArgumentException("Encryption key must be exactly 32 bytes long");
			}
			$this->encryptionKeys[$printableId] = $key;
		}else{
			throw new \InvalidArgumentException("Unknown pack ID $printableId");
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
