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

use pocketmine\plugin\PluginBase;

/**
 * @deprecated
 *
 * Interface implemented by things that want to execute commands via {@link PluginCommand}.
 * This is implemented by {@link PluginBase} by default to allow automagically registering
 * {@link PluginBase::onCommand()} to receive commands defined in `plugin.yml`.
 */
interface CommandExecutor{

	/**
	 * @param string[] $args
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool;

}
