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

use PHPUnit\Framework\TestCase;
use function yaml_parse;

class YamlEditorTest extends TestCase{

	private const DOCUMENT = <<<'YAML'
#header
server:
  #name shown in the list
  motd: "Old # not a comment" # keep me
  port: 19132
  ipv6:
    enabled: true

stack:
  - a.zip
  #trailing comment belongs to the next key
empty: {}
YAML;

	public function testReplaceKeepsComments() : void{
		$editor = new YamlEditor(self::DOCUMENT);
		$editor->set(["server", "motd"], "New #1");
		$text = $editor->getText();

		self::assertStringContainsString("  #name shown in the list\n  motd: \"New #1\" # keep me\n", $text);
		self::assertStringContainsString("#header\n", $text);
		self::assertSame("New #1", yaml_parse($text)["server"]["motd"]);
	}

	public function testReplaceBlockValue() : void{
		$editor = new YamlEditor(self::DOCUMENT);
		$editor->set(["stack"], ["b.zip", "c.zip"]);
		$text = $editor->getText();

		self::assertSame(["b.zip", "c.zip"], yaml_parse($text)["stack"]);
		self::assertStringContainsString("#trailing comment belongs to the next key\n", $text);
	}

	public function testAddMissingKeys() : void{
		$editor = new YamlEditor(self::DOCUMENT);
		$editor->set(["server", "ipv6", "port"], 19133);
		$editor->set(["empty", "nested", "value"], false);
		$editor->set(["brand-new"], ["x" => 1]);

		$parsed = yaml_parse($editor->getText());
		self::assertSame(["enabled" => true, "port" => 19133], $parsed["server"]["ipv6"]);
		self::assertSame(["nested" => ["value" => false]], $parsed["empty"]);
		self::assertSame(["x" => 1], $parsed["brand-new"]);
		self::assertSame(19132, $parsed["server"]["port"]);
	}

	public function testRefusesToWalkIntoScalar() : void{
		$editor = new YamlEditor(self::DOCUMENT);
		$this->expectException(\InvalidArgumentException::class);
		$editor->set(["server", "port", "nested"], 1);
	}

	public function testRefusesToWalkIntoSequence() : void{
		$editor = new YamlEditor(self::DOCUMENT);
		$this->expectException(\InvalidArgumentException::class);
		$editor->set(["stack", "nested"], 1);
	}
}
