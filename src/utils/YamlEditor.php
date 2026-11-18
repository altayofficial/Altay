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

namespace pocketmine\utils;

use function array_splice;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function json_decode;
use function json_encode;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Changes values in a YAML document without re-emitting the whole thing, so comments and layout of a hand edited
 * config survive. Only block style maps can be walked into. New values are written as JSON, which is valid YAML flow
 * syntax, so lists and maps end up on one line.
 *
 * @internal
 */
final class YamlEditor{
	/** @var string[] */
	private array $lines;

	public function __construct(string $text){
		$this->lines = explode("\n", str_replace("\r\n", "\n", $text), limit: PHP_INT_MAX);
	}

	public function getText() : string{
		return implode("\n", $this->lines);
	}

	/**
	 * Sets the value at the given path, adding any keys that are missing along the way.
	 *
	 * @param string[] $path
	 * @phpstan-param list<string> $path
	 *
	 * @throws \InvalidArgumentException if the path runs into something that isn't a block map
	 */
	public function set(array $path, mixed $value) : void{
		if(count($path) === 0){
			throw new \InvalidArgumentException("Path cannot be empty");
		}
		$encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);

		$parentIndent = -1;
		$start = 0;
		$end = $this->findBlockEnd(-1, -1);
		foreach($path as $depth => $key){
			$isLast = $depth === count($path) - 1;
			$childIndent = $this->findChildIndent($start, $end, $parentIndent);
			$line = $this->findKey($key, $start, $end, $childIndent);

			if($line === null){
				$insertAt = $end;
				if($parentIndent === -1 && $insertAt > 0){
					//keep top level sections apart like the rest of the file
					array_splice($this->lines, $insertAt++, 0, [""]);
				}
				$prefix = str_repeat(" ", $childIndent) . self::encodeKey($key) . ":";
				array_splice($this->lines, $insertAt, 0, [$isLast ? $prefix . " " . $encoded : $prefix]);
				$parentIndent = $childIndent;
				$start = $end = $insertAt + 1;
				continue;
			}

			[$keyPart, $inlineValue, $comment] = self::splitLine($this->lines[$line]);
			if($isLast){
				$blockEnd = $this->findBlockEnd($line, $childIndent);
				array_splice($this->lines, $line + 1, $blockEnd - $line - 1);
				$this->lines[$line] = $keyPart . " " . $encoded . $comment;
				return;
			}

			if($inlineValue !== ""){
				if(!in_array($inlineValue, ["{}", "~", "null"], true)){
					throw new \InvalidArgumentException("Can't walk into \"$key\", it holds an inline value");
				}
				$this->lines[$line] = $keyPart . $comment;
			}
			$parentIndent = $childIndent;
			$start = $line + 1;
			$end = $this->findBlockEnd($line, $childIndent);
		}
	}

	private static function isContent(string $line) : bool{
		$trimmed = ltrim($line);
		return $trimmed !== "" && $trimmed[0] !== "#";
	}

	private static function getIndent(string $line) : int{
		return strlen($line) - strlen(ltrim($line, " "));
	}

	private static function isSequenceItem(string $line) : bool{
		$trimmed = ltrim($line, " ");
		return $trimmed === "-" || str_starts_with($trimmed, "- ");
	}

	/**
	 * Returns the index just past the last line belonging to the value of the key on the given line. Comments and
	 * blank lines after the last real line are left to whatever comes next.
	 */
	private function findBlockEnd(int $line, int $indent) : int{
		$last = $line;
		for($i = $line + 1, $count = count($this->lines); $i < $count; $i++){
			$current = $this->lines[$i];
			if(!self::isContent($current)){
				continue;
			}
			$currentIndent = self::getIndent($current);
			//yaml lets a list sit at the same indent as its key
			if($currentIndent > $indent || ($currentIndent === $indent && $line !== -1 && self::isSequenceItem($current))){
				$last = $i;
			}else{
				break;
			}
		}
		return $last + 1;
	}

	private function findChildIndent(int $start, int $end, int $parentIndent) : int{
		for($i = $start; $i < $end; $i++){
			if(self::isContent($this->lines[$i])){
				return self::getIndent($this->lines[$i]);
			}
		}
		return $parentIndent === -1 ? 0 : $parentIndent + 2;
	}

	private function findKey(string $key, int $start, int $end, int $childIndent) : ?int{
		for($i = $start; $i < $end; $i++){
			$line = $this->lines[$i];
			if(!self::isContent($line) || self::getIndent($line) !== $childIndent){
				continue;
			}
			if(self::isSequenceItem($line)){
				throw new \InvalidArgumentException("Can't look up \"$key\" in a sequence");
			}
			if(self::parseKey(trim($line)) === $key){
				return $i;
			}
		}
		return null;
	}

	private static function parseKey(string $content) : ?string{
		if(preg_match('/^("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[^\s#\'"][^#]*?)\s*:(?:\s|$)/', $content, $matches) !== 1){
			return null;
		}
		$key = $matches[1];
		if($key[0] === '"'){
			$decoded = json_decode($key);
			return is_string($decoded) ? $decoded : null;
		}
		if($key[0] === "'"){
			return str_replace("''", "'", substr($key, 1, -1));
		}
		return $key;
	}

	private static function encodeKey(string $key) : string{
		return preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\-]*$/', $key) === 1 ? $key : json_encode($key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
	}

	/**
	 * Splits a "key: value # comment" line into the part up to and including the colon, the inline value and the
	 * comment (with its leading whitespace), so the value can be swapped out on its own.
	 *
	 * @return string[]
	 * @phpstan-return array{string, string, string}
	 */
	private static function splitLine(string $line) : array{
		$indent = self::getIndent($line);
		$content = substr($line, $indent);
		if(preg_match('/^("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[^\s#\'"][^#]*?)\s*:/', $content, $matches) !== 1){
			throw new \InvalidArgumentException("Not a key line: $line");
		}
		$keyPart = substr($line, 0, $indent) . $matches[0];
		$rest = substr($content, strlen($matches[0]));

		$valueStart = strlen($rest) - strlen(ltrim($rest));
		$commentStart = null;
		$i = $valueStart;
		$length = strlen($rest);
		if($i < $length && ($rest[$i] === '"' || $rest[$i] === "'")){
			//skip over the quoted scalar so a # inside it isn't mistaken for a comment
			$quote = $rest[$i];
			for($i++; $i < $length; $i++){
				if($quote === '"' && $rest[$i] === "\\"){
					$i++;
				}elseif($rest[$i] === $quote){
					if($quote === "'" && $i + 1 < $length && $rest[$i + 1] === "'"){
						$i++;
						continue;
					}
					$i++;
					break;
				}
			}
		}
		for(; $i < $length; $i++){
			if($rest[$i] === "#" && ($i === 0 || $rest[$i - 1] === " " || $rest[$i - 1] === "\t")){
				$commentStart = $i;
				break;
			}
		}

		if($commentStart === null){
			return [$keyPart, trim($rest), ""];
		}
		$value = rtrim(substr($rest, 0, $commentStart));
		return [$keyPart, trim($value), " " . substr($rest, $commentStart)];
	}
}
