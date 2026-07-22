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

namespace pocketmine\command;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginOwned;

/**
 * @internal Only used to route plugin.yml commands to {@link PluginBase::onCommand()}.
 * Use {@link Command} instead of this class.
 */
final class PluginCommand extends Command implements PluginOwned{
	public function __construct(
		string $name,
		private Plugin $owner,
		private CommandExecutor $executor
	){
		parent::__construct($name);
		$this->usageMessage = "";
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){

		if(!$this->owner->isEnabled()){
			return false;
		}

		$success = $this->executor->onCommand($sender, $this, $commandLabel, $args);

		if(!$success && $this->usageMessage !== ""){
			throw new InvalidCommandSyntaxException();
		}

		return $success;
	}

	public function getOwningPlugin() : Plugin{
		return $this->owner;
	}

	public function getExecutor() : CommandExecutor{
		return $this->executor;
	}

	public function setExecutor(CommandExecutor $executor) : void{
		$this->executor = $executor;
	}
}
