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

namespace pocketmine\form\datadriven;

enum DataDrivenScreenCloseReason{
	/**
	 * The server closed it, through DataDrivenScreenSession::close() or by opening another screen.
	 */
	case Programmatic;
	/**
	 * The client closed every screen at once.
	 */
	case ProgrammaticAll;
	/**
	 * The player dismissed it, or pressed the close button of a CustomScreen.
	 */
	case ClientCanceled;
	/**
	 * One of the MessageScreen buttons was pressed.
	 */
	case Responded;
	/**
	 * The client already had something else open and refused to show it.
	 */
	case UserBusy;
	/**
	 * The client could not make sense of the screen data.
	 */
	case InvalidForm;
	/**
	 * The player left while it was open. The client never sends this one.
	 */
	case Disconnected;
}
