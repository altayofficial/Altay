<?php

/*
 *
 *      _    _ _
 *     / \  | | |_ __ _ _   _
 *    / _ \ | | __/ _` | | | |
 *   / ___ \| | || (_| | |_| |
 *  /_/   \_\_\__\__,_|\__, |
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

namespace pocketmine\world\format\io\leveldb;

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Binary;
use pocketmine\utils\Filesystem;
use pocketmine\VersionInfo;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\WorldCreationOptions;
use Symfony\Component\Filesystem\Path;
use function bin2hex;
use function random_bytes;
use function sys_get_temp_dir;

class LevelDBTest extends TestCase{

	private string $worldPath;
	private LevelDB $provider;

	protected function setUp() : void{
		$this->worldPath = Path::join(sys_get_temp_dir(), "altay-leveldb-test-" . bin2hex(random_bytes(8)));
		LevelDB::generate($this->worldPath, "test", WorldCreationOptions::create());
		$this->provider = new LevelDB($this->worldPath, new \SimpleLogger());
	}

	protected function tearDown() : void{
		$this->provider->close();
		Filesystem::recursiveUnlink($this->worldPath);
	}

	public function testDataVersionIsOnlyAdvancedWhenExplicitlyMarkedDirty() : void{
		$chunkX = 3;
		$chunkZ = -5;
		$key = LevelDB::chunkIndex($chunkX, $chunkZ) . ChunkDataKey::PM_DATA_VERSION;
		$db = $this->provider->getDatabase();
		$db->put($key, Binary::writeLLong(1));
		$chunkData = new ChunkData([], true, [], []);

		$this->provider->saveChunk($chunkX, $chunkZ, $chunkData, Chunk::DIRTY_FLAG_BLOCKS);
		$oldDataVersion = $db->get($key);
		self::assertIsString($oldDataVersion);
		self::assertSame(1, Binary::readLLong($oldDataVersion));

		$this->provider->saveChunk($chunkX, $chunkZ, $chunkData, Chunk::DIRTY_FLAG_DATA_VERSION);
		$currentDataVersion = $db->get($key);
		self::assertIsString($currentDataVersion);
		self::assertSame(VersionInfo::WORLD_DATA_VERSION, Binary::readLLong($currentDataVersion));
	}

	public function testSubChunkPalettesSurviveRepeatedSaves() : void{
		$air = VanillaBlocks::AIR()->getStateId();
		$stone = VanillaBlocks::STONE()->getStateId();
		$dirt = VanillaBlocks::DIRT()->getStateId();
		$blocks = new PalettedBlockArray($air);
		$blocks->set(0, 0, 0, $stone);
		$blocks->set(1, 2, 3, $dirt);
		$chunk = new Chunk([], true);
		$chunk->setSubChunk(0, new SubChunk($air, [$blocks], new PalettedBlockArray(BiomeIds::PLAINS)));
		$chunkData = new ChunkData($chunk->getSubChunks(), true, [], []);

		//the second save encodes the palette from the cache, it has to write the same bytes
		$this->provider->saveChunk(0, 0, $chunkData, Chunk::DIRTY_FLAGS_ALL);
		$key = LevelDB::chunkIndex(0, 0) . ChunkDataKey::SUBCHUNK . "\x00";
		$first = $this->provider->getDatabase()->get($key);
		$this->provider->saveChunk(0, 0, $chunkData, Chunk::DIRTY_FLAGS_ALL);
		self::assertSame($first, $this->provider->getDatabase()->get($key));

		$loaded = $this->provider->loadChunk(0, 0);
		self::assertNotNull($loaded);
		$loadedBlocks = $loaded->getData()->getSubChunks()[0]->getBlockLayers()[0];
		self::assertSame($stone, $loadedBlocks->get(0, 0, 0));
		self::assertSame($dirt, $loadedBlocks->get(1, 2, 3));
		self::assertSame($air, $loadedBlocks->get(5, 5, 5));
	}
}
