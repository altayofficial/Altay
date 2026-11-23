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

namespace pocketmine\network\mcpe\nethernet;

use altay\netherlib\ipc\NetherLibToUserThreadMessageSender;
use altay\netherlib\ipc\UserToNetherLibThreadMessageReceiver;
use altay\netherlib\NetherNetException;
use pmmp\thread\Thread as NativeThread;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\thread\Thread;
use pocketmine\GarbageCollectorManager;
use pocketmine\thread\ThreadCrashException;
use pocketmine\timings\Timings;
use function hrtime;
use function ini_set;
use function intdiv;
use function usleep;

class NetherNetServerThread extends Thread{

	private const TICK_INTERVAL_MICROS = 5000;

	protected bool $ready = false;
	protected string $mainPath;
	/** @phpstan-var NonThreadSafeValue<NetherNetServerFactory> */
	protected NonThreadSafeValue $factory;

	/**
	 * @phpstan-param ThreadSafeArray<int, string> $mainToThreadBuffer
	 * @phpstan-param ThreadSafeArray<int, string> $threadToMainBuffer
	 */
	public function __construct(
		protected ThreadSafeLogger $logger,
		protected ThreadSafeArray $mainToThreadBuffer,
		protected ThreadSafeArray $threadToMainBuffer,
		NetherNetServerFactory $factory,
		protected SleeperHandlerEntry $sleeperEntry
	){
		$this->mainPath = \pocketmine\PATH;
		$this->factory = new NonThreadSafeValue($factory);
	}

	public function startAndWait(int $options = NativeThread::INHERIT_NONE) : void{
		$this->start($options);
		$this->synchronized(function() : void{
			while(!$this->ready && !$this->isTerminated()){
				$this->wait();
			}
		});
		$crashInfo = $this->getCrashInfo();
		if($crashInfo !== null){
			if($crashInfo->getType() === NetherNetException::class){
				throw new NetherNetException($crashInfo->getMessage());
			}
			throw new ThreadCrashException("NetherNet thread failed to start", $crashInfo);
		}
	}

	protected function onRun() : void{
		ini_set("display_errors", '1');
		ini_set("display_startup_errors", '1');
		\GlobalLogger::set($this->logger);

		Timings::init();
		//a peer connection is a web of objects that point back at each other - the event handlers a
		//connection registers close over the connection itself - so nothing here is freed by
		//refcounting alone. Without this the thread grows by every connection it has ever served.
		$cycleGcManager = new GarbageCollectorManager($this->logger, null);

		$server = $this->factory->deserialize()->make($this->logger);
		$server->start(new NetherLibToUserThreadMessageSender(
			new SnoozeAwarePthreadsChannelWriter($this->threadToMainBuffer, $this->sleeperEntry->createNotifier())
		));

		$commandReceiver = new UserToNetherLibThreadMessageReceiver(
			new PthreadsChannelReader($this->mainToThreadBuffer)
		);

		$this->synchronized(function() : void{
			$this->ready = true;
			$this->notify();
		});

		while(!$this->isKilled && !$commandReceiver->isShutdownRequested()){
			$start = hrtime(true);

			while($commandReceiver->handle($server));
			if($commandReceiver->isShutdownRequested()){
				break;
			}
			$server->tick();
			$cycleGcManager->maybeCollectCycles();

			$elapsedMicros = intdiv(hrtime(true) - $start, 1000);
			if($elapsedMicros < self::TICK_INTERVAL_MICROS){
				usleep(self::TICK_INTERVAL_MICROS - $elapsedMicros);
			}
		}

		while($commandReceiver->handle($server));
		$server->shutdown();
	}

	public function getThreadName() : string{
		return "NetherNet";
	}
}
