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

namespace pocketmine\event\entity;

use pocketmine\entity\Living;
use pocketmine\entity\object\AreaEffectCloud;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

/**
 * Called when an area effect cloud applies effects to entities.
 *
 * @phpstan-extends EntityEvent<AreaEffectCloud>
 */
class AreaEffectCloudApplyEvent extends EntityEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @param Living[] $affectedEntities
	 */
	public function __construct(
		AreaEffectCloud $entity,
		protected array $affectedEntities
	){
		$this->entity = $entity;
	}

	/**
	 * @return AreaEffectCloud
	 */
	public function getEntity(){
		return $this->entity;
	}

	/**
	 * Returns the affected entities.
	 *
	 * @return Living[]
	 */
	public function getAffectedEntities() : array{
		return $this->affectedEntities;
	}
}
