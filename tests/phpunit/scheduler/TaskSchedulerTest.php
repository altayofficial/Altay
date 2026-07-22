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

namespace pocketmine\scheduler;

use PHPUnit\Framework\TestCase;

class TaskSchedulerTest extends TestCase{

	/** @var TaskScheduler */
	private $scheduler;

	public function setUp() : void{
		$this->scheduler = new TaskScheduler();
	}

	public function tearDown() : void{
		$this->scheduler->shutdown();
	}

	public function testCancel() : void{
		$task = $this->scheduler->scheduleTask(new ClosureTask(function() : void{
			throw new CancelTaskException();
		}));
		$this->scheduler->mainThreadHeartbeat(0);
		self::assertTrue($task->isCancelled(), "Task was not cancelled");
	}
}
