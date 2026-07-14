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

namespace pocketmine\permission;

final class PermissibleBase implements Permissible{
	use PermissibleDelegateTrait;

	private PermissibleInternal $permissibleBase;

	/**
	 * @param bool[] $basePermissions
	 * @phpstan-param array<string, bool> $basePermissions
	 */
	public function __construct(array $basePermissions){
		$this->permissibleBase = new PermissibleInternal($basePermissions);
		$this->perm = $this->permissibleBase;
	}

	public function __destruct(){
		//permission subscriptions need to be cleaned up explicitly
		$this->permissibleBase->destroyCycles();
	}
}
