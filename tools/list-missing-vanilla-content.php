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

namespace pocketmine\tools\list_missing_vanilla_content;

use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function count;
use function dirname;
use function file_put_contents;
use function fwrite;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function sprintf;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const SORT_STRING;
use const STDERR;
use const STDOUT;

require dirname(__DIR__) . '/vendor/autoload.php';

if(!isset($argv) || count($argv) > 2){
	fwrite(STDERR, "Optional argument: output JSON file path\n");
	exit(1);
}
$outputFile = $argv[1] ?? null;

/**
 * @return array{
 *     missing: array<string, array{states: int, failed: int, reason: string}>,
 *     supported: array<string, true>
 * }
 */
function scanBlocks() : array{
	$deserializer = GlobalBlockStateHandlers::getDeserializer();

	$states = BlockStateDictionary::loadStatesFromPalette(Filesystem::fileGetContents(\pocketmine\BEDROCK_DATA_PATH . 'block_palette.nbt'));

	$missing = [];
	$supported = [];
	foreach($states as $state){
		$name = $state->getName();
		try{
			$deserializer->deserialize($state);
			$supported[$name] = true;
			continue;
		}catch(UnsupportedBlockStateException $e){
			$reason = $e->getMessage();
		}catch(BlockStateDeserializeException $e){
			$reason = "malformed state: " . $e->getMessage();
		}

		if(!isset($missing[$name])){
			$missing[$name] = ["states" => 0, "failed" => 0, "reason" => $reason];
		}
		$missing[$name]["failed"]++;
	}

	foreach($states as $state){
		$name = $state->getName();
		if(isset($missing[$name])){
			$missing[$name]["states"]++;
		}
	}

	ksort($missing, SORT_STRING);
	return ["missing" => $missing, "supported" => $supported];
}

/**
 * @param array<string, true> $supportedBlocks
 *
 * @return array<string, string> item id => reason
 */
function scanItems(array $supportedBlocks) : array{
	$deserializer = GlobalItemDataHandlers::getDeserializer();
	$upgrader = GlobalItemDataHandlers::getUpgrader();
	$blockItemIdMap = BlockItemIdMap::getInstance();

	$palette = json_decode(Filesystem::fileGetContents(\pocketmine\BEDROCK_DATA_PATH . 'item_palette.json'), associative: true, flags: JSON_THROW_ON_ERROR);
	if(!is_array($palette) || !is_array($palette["items"] ?? null)){
		fwrite(STDERR, "Invalid item palette, missing \"items\" list\n");
		exit(1);
	}

	$missing = [];
	foreach($palette["items"] as $entry){
		if(!is_array($entry) || !is_string($entry["name"] ?? null)){
			continue;
		}
		$name = $entry["name"];

		//air is never deserialized as an item type
		if($name === "minecraft:air"){
			continue;
		}

		if($deserializer->getDeserializerForId($name) !== null){
			continue;
		}

		//legacy IDs are still present in the palette (e.g. minecraft:wood), so normalize them before checking
		try{
			$data = $upgrader->upgradeItemTypeDataString($name, 0, 1, null)->getTypeData();
		}catch(\Throwable $e){
			$missing[$name] = "failed to upgrade item data: " . $e->getMessage();
			continue;
		}

		$blockId = $blockItemIdMap->lookupBlockId($data->getName());
		if($blockId !== null && isset($supportedBlocks[$blockId])){
			continue;
		}

		try{
			$deserializer->deserializeType($data);
		}catch(\Throwable $e){
			$missing[$name] = $blockId !== null ?
				"block item of unimplemented block $blockId" :
				$e->getMessage();
		}
	}

	ksort($missing, SORT_STRING);
	return $missing;
}

$blocks = scanBlocks();
$items = scanItems($blocks["supported"]);

$fullyMissingBlocks = [];
$partialBlocks = [];
foreach(Utils::stringifyKeys($blocks["missing"]) as $name => $info){
	if($info["failed"] === $info["states"]){
		$fullyMissingBlocks[$name] = $info;
	}else{
		$partialBlocks[$name] = $info;
	}
}

fwrite(STDOUT, sprintf("Unimplemented blocks (%d):%s", count($fullyMissingBlocks), PHP_EOL));
foreach(Utils::stringifyKeys($fullyMissingBlocks) as $name => $info){
	fwrite(STDOUT, sprintf("  %s (%d states) - %s%s", $name, $info["states"], $info["reason"], PHP_EOL));
}

fwrite(STDOUT, sprintf("%sBlocks with unimplemented states (%d):%s", PHP_EOL, count($partialBlocks), PHP_EOL));
foreach(Utils::stringifyKeys($partialBlocks) as $name => $info){
	fwrite(STDOUT, sprintf("  %s (%d/%d states failed) - %s%s", $name, $info["failed"], $info["states"], $info["reason"], PHP_EOL));
}

fwrite(STDOUT, sprintf("%sUnimplemented items (%d):%s", PHP_EOL, count($items), PHP_EOL));
foreach(Utils::stringifyKeys($items) as $name => $reason){
	fwrite(STDOUT, sprintf("  %s - %s%s", $name, $reason, PHP_EOL));
}

if($outputFile !== null){
	file_put_contents($outputFile, json_encode([
		"blocks" => $fullyMissingBlocks,
		"partialBlocks" => $partialBlocks,
		"items" => $items
	], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
	fwrite(STDOUT, PHP_EOL . "Written to $outputFile" . PHP_EOL);
}
