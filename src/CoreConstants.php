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

namespace pocketmine;

use function define;
use function defined;
use function dirname;

// composer autoload doesn't use require_once and also pthreads can inherit things
if(defined('pocketmine\_CORE_CONSTANTS_INCLUDED')){
	return;
}
define('pocketmine\_CORE_CONSTANTS_INCLUDED', true);

define('pocketmine\PATH', dirname(__DIR__) . '/');
define('pocketmine\RESOURCE_PATH', dirname(__DIR__) . '/resources/');
define('pocketmine\BEDROCK_DATA_PATH', dirname(__DIR__) . '/vendor/altayofficial/bedrock-data/');
define('pocketmine\LOCALE_DATA_PATH', dirname(__DIR__) . '/resources/translations/');
define('pocketmine\BEDROCK_BLOCK_UPGRADE_SCHEMA_PATH', dirname(__DIR__) . '/vendor/altayofficial/worldupgraderschemas/');
define('pocketmine\BEDROCK_ITEM_UPGRADE_SCHEMA_PATH', dirname(__DIR__) . '/vendor/altayofficial/worldupgraderschemas/');
define('pocketmine\COMPOSER_AUTOLOADER_PATH', dirname(__DIR__) . '/vendor/autoload.php');
