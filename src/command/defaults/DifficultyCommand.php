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

namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\ServerProperties;
use pocketmine\world\World;
use function count;

class DifficultyCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"difficulty",
			KnownTranslationFactory::pocketmine_command_difficulty_description(),
			KnownTranslationFactory::commands_difficulty_usage()
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_DIFFICULTY);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) !== 1){
			throw new InvalidCommandSyntaxException();
		}

		$difficulty = World::getDifficultyFromString($args[0]);

		if($sender->getServer()->isHardcore()){
			$difficulty = World::DIFFICULTY_HARD;
		}

		if($difficulty !== -1){
			$sender->getServer()->getConfigGroup()->setConfigInt(ServerProperties::DIFFICULTY, $difficulty);

			//TODO: add per-world support
			foreach($sender->getServer()->getWorldManager()->getWorlds() as $world){
				$world->setDifficulty($difficulty);
			}

			Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_difficulty_success((string) $difficulty));
		}else{
			throw new InvalidCommandSyntaxException();
		}

		return true;
	}
}
