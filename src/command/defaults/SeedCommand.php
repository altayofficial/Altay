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

use pocketmine\command\CommandSender;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;

class SeedCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"seed",
			KnownTranslationFactory::pocketmine_command_seed_description()
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_SEED);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player){
			$seed = $sender->getPosition()->getWorld()->getSeed();
		}else{
			$seed = $sender->getServer()->getWorldManager()->getDefaultWorld()->getSeed();
		}
		$sender->sendMessage(KnownTranslationFactory::commands_seed_success((string) $seed));

		return true;
	}
}
