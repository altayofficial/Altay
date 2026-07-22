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

namespace pmmp\TesterPlugin;

use function time;

abstract class Test{
	const RESULT_WAITING = -1;
	const RESULT_OK = 0;
	const RESULT_FAILED = 1;
	const RESULT_ERROR = 2;

	private int $result = Test::RESULT_WAITING;
	private int $startTime;
	private int $timeout = 60; //seconds

	public function __construct(
		private \Logger $logger,
		private string $name,
		private string $description
	){}

	final public function start() : void{
		$this->startTime = time();
		try{
			$this->run();
		}catch(TestFailedException $e){
			$this->logger->error($e->getMessage());
			$this->setResult(Test::RESULT_FAILED);
		}catch(\Throwable $e){
			$this->logger->logException($e);
			$this->setResult(Test::RESULT_ERROR);
		}
	}

	public function tick() : void{

	}

	/**
	 * @throws TestFailedException
	 */
	abstract public function run() : void;

	public function isFinished() : bool{
		return $this->result !== Test::RESULT_WAITING;
	}

	public function isTimedOut() : bool{
		return !$this->isFinished() && time() - $this->timeout > $this->startTime;
	}

	protected function setTimeout(int $timeout) : void{
		$this->timeout = $timeout;
	}

	public function getResult() : int{
		return $this->result;
	}

	public function setResult(int $result) : void{
		$this->result = $result;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getDescription() : string{
		return $this->description;
	}
}
