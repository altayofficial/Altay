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

namespace pmmp\TesterPlugin\hopper;

use pmmp\TesterPlugin\Main;
use pmmp\TesterPlugin\Test;
use pmmp\TesterPlugin\TestFailedException;
use pocketmine\block\tile\Container;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

abstract class HopperTestBase extends Test implements ChunkLoader{
	protected const AREA_SIZE = 16;
	protected const AREA_HEIGHT = 24;
	protected const AREA_Y = 100;
	private const ENTITY_SEARCH_PADDING = 4;

	protected World $world;
	protected int $areaX;
	protected int $areaZ;

	private \Logger $testLogger;
	/** @phpstan-var TaskHandler<ClosureTask>|null */
	private ?TaskHandler $tickHandler = null;
	private int $elapsedTicks = 0;

	public function __construct(\Logger $logger, protected Main $plugin, string $name, string $description){
		parent::__construct($logger, $name, $description);
		$this->testLogger = $logger;
	}

	final public function run() : void{
		$world = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
		if($world === null){
			throw new TestFailedException("The server has no default world to build the test setup in");
		}
		$this->world = $world;

		$spawn = $world->getSpawnLocation();
		$chunkX = $spawn->getFloorX() >> Chunk::COORD_BIT_SIZE;
		$chunkZ = $spawn->getFloorZ() >> Chunk::COORD_BIT_SIZE;
		$this->areaX = $chunkX << Chunk::COORD_BIT_SIZE;
		$this->areaZ = $chunkZ << Chunk::COORD_BIT_SIZE;

		$world->registerChunkLoader($this, $chunkX, $chunkZ);
		$world->orderChunkPopulation($chunkX, $chunkZ, $this)->onCompletion(
			function() : void{
				$this->startTicking();
			},
			function() : void{
				$this->testLogger->error("Failed to generate the chunk the test setup is built in");
				$this->finish(Test::RESULT_ERROR);
			}
		);
	}

	private function startTicking() : void{
		try{
			$this->clearArea();
			$this->setUpArea();
		}catch(TestFailedException $e){
			$this->testLogger->error($e->getMessage());
			$this->finish(Test::RESULT_FAILED);
			return;
		}catch(\Throwable $e){
			$this->testLogger->logException($e);
			$this->finish(Test::RESULT_ERROR);
			return;
		}

		$this->tickHandler = $this->plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			$this->tickTest();
		}), 1);
	}

	private function tickTest() : void{
		if($this->isTimedOut()){
			$this->cleanUp();
			return;
		}

		$this->elapsedTicks++;
		try{
			if($this->shouldFreezeDroppedItems()){
				$this->freezeDroppedItems();
			}
			$this->checkInvariants($this->elapsedTicks);
			if($this->elapsedTicks >= $this->getDurationTicks()){
				$this->checkOutcome();
				$this->finish(Test::RESULT_OK);
			}
		}catch(TestFailedException $e){
			$this->testLogger->error($e->getMessage());
			$this->finish(Test::RESULT_FAILED);
		}catch(\Throwable $e){
			$this->testLogger->logException($e);
			$this->finish(Test::RESULT_ERROR);
		}
	}

	private function finish(int $result) : void{
		$this->cleanUp();
		$this->setResult($result);
	}

	private function cleanUp() : void{
		$this->tickHandler?->cancel();
		$this->tickHandler = null;
		try{
			$this->tearDownArea();
			$this->clearArea();
		}catch(\Throwable $e){
			$this->testLogger->logException($e);
		}
		$this->world->unregisterChunkLoader($this, $this->areaX >> Chunk::COORD_BIT_SIZE, $this->areaZ >> Chunk::COORD_BIT_SIZE);
	}

	abstract protected function setUpArea() : void;

	abstract protected function checkInvariants(int $tick) : void;

	abstract protected function checkOutcome() : void;

	abstract protected function getDurationTicks() : int;

	protected function tearDownArea() : void{

	}

	protected function areaPos(int $x, int $y, int $z) : Vector3{
		return new Vector3($this->areaX + $x, self::AREA_Y + $y, $this->areaZ + $z);
	}

	protected function entityBoundingBox() : AxisAlignedBB{
		return new AxisAlignedBB(
			$this->areaX - self::ENTITY_SEARCH_PADDING,
			World::Y_MIN,
			$this->areaZ - self::ENTITY_SEARCH_PADDING,
			$this->areaX + self::AREA_SIZE + self::ENTITY_SEARCH_PADDING,
			self::AREA_Y + self::AREA_HEIGHT,
			$this->areaZ + self::AREA_SIZE + self::ENTITY_SEARCH_PADDING
		);
	}

	protected function shouldFreezeDroppedItems() : bool{
		return true;
	}

	private function freezeDroppedItems() : void{
		foreach($this->world->getNearbyEntities($this->entityBoundingBox()) as $entity){
			if($entity instanceof ItemEntity && $entity->hasGravity()){
				$entity->setHasGravity(false);
				$entity->setMotion(new Vector3(0, 0, 0));
			}
		}
	}

	/**
	 * @throws TestFailedException
	 */
	protected function getContainerInventory(Vector3 $pos) : Inventory{
		$tile = $this->world->getTile($pos);
		if(!$tile instanceof Container){
			throw new TestFailedException("Expected a container tile at " . $pos->__toString() . ", but found " . ($tile === null ? "nothing" : $tile::class));
		}
		return $tile->getInventory();
	}

	/**
	 * @param Inventory[] $inventories
	 */
	protected function countItems(array $inventories) : int{
		$count = 0;
		foreach($inventories as $inventory){
			for($slot = 0, $size = $inventory->getSize(); $slot < $size; $slot++){
				$count += $inventory->getItem($slot)->getCount();
			}
		}
		return $count;
	}

	/**
	 * @phpstan-param (\Closure(Item) : bool)|null $filter
	 */
	protected function countDroppedItems(?\Closure $filter = null) : int{
		$count = 0;
		foreach($this->world->getNearbyEntities($this->entityBoundingBox()) as $entity){
			if(!$entity instanceof ItemEntity || $entity->isFlaggedForDespawn()){
				continue;
			}
			$item = $entity->getItem();
			if($filter === null || $filter($item)){
				$count += $item->getCount();
			}
		}
		return $count;
	}

	private function clearArea() : void{
		foreach($this->world->getNearbyEntities($this->entityBoundingBox()) as $entity){
			if($entity instanceof ItemEntity){
				$entity->close();
			}
		}

		$air = VanillaBlocks::AIR();
		for($x = 0; $x < self::AREA_SIZE; $x++){
			for($z = 0; $z < self::AREA_SIZE; $z++){
				for($y = 0; $y < self::AREA_HEIGHT; $y++){
					$this->world->setBlockAt($this->areaX + $x, self::AREA_Y + $y, $this->areaZ + $z, $air, false);
				}
			}
		}
	}
}
