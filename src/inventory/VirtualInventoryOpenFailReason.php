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

namespace pocketmine\inventory;

/**
 * Why a client refused to show a virtual inventory.
 */
enum VirtualInventoryOpenFailReason{
	/** The player already had another screen open, e.g. chat or pause. Trying again later usually works. */
	case PLAYER_BUSY;
	/** The client wasn't ready to show screens yet, e.g. still loading in. */
	case CLIENT_NOT_READY;
	/** The client didn't accept the contents it was sent. */
	case INVALID_CONTENTS;
	/** The client failed to build the screen. */
	case UI_CREATION_FAILED;
	/** The client cancelled the screen before it finished opening. */
	case UI_CANCELLED;
}
