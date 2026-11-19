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

use PHPUnit\Framework\TestCase;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use function file_put_contents;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class ServerConfigGroupTest extends TestCase{

	private string $file;

	protected function setUp() : void{
		$file = tempnam(sys_get_temp_dir(), "altay");
		self::assertNotFalse($file);
		$this->file = $file;
		file_put_contents($this->file, "#keep me\nserver:\n  motd: Old\nplayer:\n  max-players: 20\n");
	}

	protected function tearDown() : void{
		@unlink($this->file);
	}

	public function testSaveKeepsHandEdits() : void{
		$group = new ServerConfigGroup(new Config($this->file, Config::YAML));
		file_put_contents($this->file, "#keep me\nserver:\n  motd: Old\nplayer:\n  max-players: 50\n");

		$group->setConfigString(ServerProperties::MOTD, "New");
		$group->save();

		$saved = Filesystem::fileGetContents($this->file);
		self::assertTrue(str_contains($saved, "#keep me"));
		$parsed = new Config($this->file, Config::YAML);
		self::assertSame("New", $parsed->getNested("server.motd"));
		self::assertSame(50, $parsed->getNested("player.max-players"));
	}

	public function testMissingSettingUsesBundledDefault() : void{
		$group = new ServerConfigGroup(new Config($this->file, Config::YAML), ["player" => ["pvp" => true]]);
		self::assertTrue($group->getConfigBool(ServerProperties::PVP));
		self::assertSame(20, $group->getConfigInt(ServerProperties::MAX_PLAYERS, 10));
	}
}
