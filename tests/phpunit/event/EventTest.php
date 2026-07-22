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

namespace pocketmine\event;

use PHPUnit\Framework\TestCase;
use pocketmine\event\fixtures\TestChildEvent;
use pocketmine\event\fixtures\TestGrandchildEvent;
use pocketmine\event\fixtures\TestParentEvent;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginManager;
use pocketmine\Server;

final class EventTest extends TestCase{

	private Plugin $mockPlugin;
	private PluginManager $pluginManager;

	protected function setUp() : void{
		HandlerListManager::global()->unregisterAll();

		//TODO: this is a really bad hack and could break any time if PluginManager decides to access its Server field
		//we really need to make it possible to register events without a Plugin or Server context
		$mockServer = $this->createMock(Server::class);
		$this->mockPlugin = self::createStub(Plugin::class);
		$this->mockPlugin->method('isEnabled')->willReturn(true);

		$this->pluginManager = new PluginManager($mockServer, null);
	}

	public static function tearDownAfterClass() : void{
		HandlerListManager::global()->unregisterAll();
	}

	public function testHandlerInheritance() : void{
		$expectedOrder = [
			TestGrandchildEvent::class,
			TestChildEvent::class,
			TestParentEvent::class
		];
		$actualOrder = [];

		foreach($expectedOrder as $class){
			$this->pluginManager->registerEvent(
				$class,
				function(TestParentEvent $event) use (&$actualOrder, $class) : void{
					$actualOrder[] = $class;
				},
				EventPriority::NORMAL,
				$this->mockPlugin
			);
		}

		$event = new TestGrandchildEvent();
		$event->call();

		self::assertSame($expectedOrder, $actualOrder, "Expected event handlers to be called from most specific to least specific");
	}
}
