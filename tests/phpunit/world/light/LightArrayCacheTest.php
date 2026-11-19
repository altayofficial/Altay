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

namespace pocketmine\world\light;

use PHPUnit\Framework\TestCase;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\LightArray;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use Symfony\Component\Filesystem\Path;
use function array_fill;
use function file_put_contents;
use function is_string;
use function mkdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class LightArrayCacheTest extends TestCase{
	private const MIN_SUB_CHUNK = -4;
	private const MAX_SUB_CHUNK = 19;

	private string $path;
	private LightArrayCache $cache;
	private RecordingLogger $logger;

	protected function setUp() : void{
		$this->path = Path::join(sys_get_temp_dir(), uniqid("light-cache-", true));
		mkdir($this->path);
		$this->logger = new RecordingLogger();
		$this->cache = $this->open("light_cache") ?? throw new \LogicException("Cache should open in an empty directory");
	}

	private function open(string $name) : ?LightArrayCache{
		return LightArrayCache::open(Path::join($this->path, $name), $this->logger);
	}

	protected function tearDown() : void{
		$this->cache->close();
		Filesystem::recursiveUnlink($this->path);
	}

	private static function chunk() : Chunk{
		$subChunks = [];
		for($y = self::MIN_SUB_CHUNK; $y <= self::MAX_SUB_CHUNK; $y++){
			$subChunks[$y] = new SubChunk(0, [], new PalettedBlockArray(0));
		}

		return new Chunk($subChunks, true);
	}

	public function testRestoresWhatWasStored() : void{
		$stored = self::chunk();
		$stored->getSubChunk(-4)->setBlockLightArray(LightArray::fill(7));
		$mixed = LightArray::fill(15);
		$mixed->set(3, 9, 12, 4);
		$stored->getSubChunk(19)->setBlockSkyLightArray($mixed);
		$heightMap = [-64, ...array_fill(0, 254, 70), 319];
		$stored->setHeightMapArray($heightMap);
		$stored->setLightPopulated(true);
		$this->cache->store(5, -3, $stored, LightArrayCache::fingerprint($stored));

		$restored = self::chunk();
		self::assertTrue($this->cache->restore(5, -3, $restored, LightArrayCache::fingerprint($restored)));
		self::assertTrue($restored->isLightPopulated());
		self::assertSame($heightMap, $restored->getHeightMapArray());
		self::assertTrue($restored->getSubChunk(-4)->getBlockLightArray()->isUniform(7));
		self::assertTrue($restored->getSubChunk(0)->getBlockLightArray()->isUniform(0));
		self::assertSame(4, $restored->getSubChunk(19)->getBlockSkyLightArray()->get(3, 9, 12));
		self::assertSame(15, $restored->getSubChunk(19)->getBlockSkyLightArray()->get(3, 9, 11));
	}

	public function testChangedLightIsWrittenAgain() : void{
		$chunk = self::chunk();
		$chunk->setLightPopulated(true);
		$this->cache->store(2, 2, $chunk, LightArrayCache::fingerprint($chunk));

		$loaded = self::chunk();
		self::assertTrue($this->cache->restore(2, 2, $loaded, LightArrayCache::fingerprint($loaded)));
		$this->cache->store(2, 2, $loaded, LightArrayCache::fingerprint($loaded));
		$loaded->getSubChunk(3)->setBlockLightArray(LightArray::fill(9));
		$this->cache->store(2, 2, $loaded, LightArrayCache::fingerprint($loaded));
		$this->cache->forget(2, 2);

		$again = self::chunk();
		self::assertTrue($this->cache->restore(2, 2, $again, LightArrayCache::fingerprint($again)));
		self::assertTrue($again->getSubChunk(3)->getBlockLightArray()->isUniform(9));
	}

	public function testLightIsNotRestoredOntoDifferentTerrain() : void{
		$stored = self::chunk();
		$stored->setBlockStateId(1, 64, 1, 5);
		$stored->setLightPopulated(true);
		$this->cache->store(4, 4, $stored, LightArrayCache::fingerprint($stored));
		self::assertTrue($this->cache->has(4, 4));

		$edited = self::chunk();
		$edited->setBlockStateId(1, 64, 1, 5);
		$edited->setBlockStateId(2, 64, 1, 9);
		self::assertFalse($this->cache->restore(4, 4, $edited, LightArrayCache::fingerprint($edited)));
		self::assertFalse($edited->isLightPopulated());

		$same = self::chunk();
		$same->setBlockStateId(1, 64, 1, 5);
		self::assertFalse($this->cache->restore(4, 4, $same, LightArrayCache::fingerprint($same)));
	}

	public function testSameTerrainIsRestored() : void{
		$stored = self::chunk();
		$stored->setBlockStateId(1, 64, 1, 5);
		$stored->setLightPopulated(true);
		$this->cache->store(4, 4, $stored, LightArrayCache::fingerprint($stored));

		$same = self::chunk();
		$same->setBlockStateId(1, 64, 1, 5);
		self::assertTrue($this->cache->restore(4, 4, $same, LightArrayCache::fingerprint($same)));
		self::assertTrue($this->cache->has(4, 4));

		$this->cache->drop(4, 4);
		self::assertFalse($this->cache->has(4, 4));
		self::assertFalse($this->cache->restore(4, 4, $same, LightArrayCache::fingerprint($same)));
	}

	public function testUnreadableCacheIsRebuilt() : void{
		$chunk = self::chunk();
		$chunk->setLightPopulated(true);
		$this->cache->store(0, 0, $chunk, LightArrayCache::fingerprint($chunk));
		$this->cache->close();

		$directory = Path::join($this->path, "light_cache");
		foreach(scandir($directory) as $file){
			if($file !== "." && $file !== ".."){
				unlink(Path::join($directory, $file));
			}
		}
		file_put_contents(Path::join($directory, "CURRENT"), "MANIFEST-999999\n");

		$this->cache = $this->open("light_cache") ?? throw new \LogicException("An unreadable cache should be rebuilt");
		self::assertSame([\LogLevel::WARNING], $this->logger->levels);
		self::assertFalse($this->cache->restore(0, 0, self::chunk(), LightArrayCache::fingerprint(self::chunk())));
	}

	public function testCacheThatCannotBeCreatedIsDisabled() : void{
		file_put_contents(Path::join($this->path, "not-a-directory"), "");

		self::assertNull($this->open(Path::join("not-a-directory", "light_cache")));
		self::assertSame([\LogLevel::WARNING, \LogLevel::ERROR], $this->logger->levels);
	}

	public function testUniformLightStaysShared() : void{
		$stored = self::chunk();
		$stored->getSubChunk(2)->setUniformBlockLight(7);
		$stored->getSubChunk(3)->setUniformBlockSkyLight(15);
		$stored->getSubChunk(4)->getBlockLightArray()->set(1, 2, 3, 9);
		$stored->setLightPopulated(true);
		$this->cache->store(6, 6, $stored, LightArrayCache::fingerprint($stored));

		self::assertSame(7, $stored->getSubChunk(2)->getUniformBlockLight());
		self::assertSame(15, $stored->getSubChunk(3)->getUniformBlockSkyLight());

		$restored = self::chunk();
		self::assertTrue($this->cache->restore(6, 6, $restored, LightArrayCache::fingerprint($restored)));
		self::assertSame(7, $restored->getSubChunk(2)->getUniformBlockLight());
		self::assertSame(15, $restored->getSubChunk(3)->getUniformBlockSkyLight());
		self::assertSame(0, $restored->getSubChunk(0)->getUniformBlockLight());
		self::assertNull($restored->getSubChunk(4)->getUniformBlockLight());
		self::assertSame(9, $restored->getSubChunk(4)->getBlockLightAt(1, 2, 3));
		self::assertSame(7, $restored->getSubChunk(2)->getBlockLightAt(5, 5, 5));
	}

	public function testUnknownChunkIsLeftUntouched() : void{
		$chunk = self::chunk();
		self::assertFalse($this->cache->restore(1, 1, $chunk, LightArrayCache::fingerprint($chunk)));
		self::assertFalse($chunk->isLightPopulated());
	}

	public function testChunkWithoutLightDropsItsEntry() : void{
		$chunk = self::chunk();
		$chunk->setLightPopulated(true);
		$this->cache->store(0, 0, $chunk, LightArrayCache::fingerprint($chunk));

		$replaced = self::chunk();
		$this->cache->store(0, 0, $replaced, LightArrayCache::fingerprint($replaced));

		self::assertFalse($this->cache->restore(0, 0, self::chunk(), LightArrayCache::fingerprint(self::chunk())));
	}
}

final class RecordingLogger extends \SimpleLogger{
	/** @var string[] */
	public array $levels = [];

	public function log($level, $message){
		$this->levels[] = is_string($level) ? $level : "";
	}
}
