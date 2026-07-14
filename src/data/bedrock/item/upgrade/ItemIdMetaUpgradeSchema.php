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

namespace pocketmine\data\bedrock\item\upgrade;

use function mb_strtolower;

final class ItemIdMetaUpgradeSchema{

	/**
	 * @param string[]   $renamedIds
	 * @param string[][] $remappedMetas
	 * @phpstan-param array<string, string> $renamedIds
	 * @phpstan-param array<string, array<int, string>> $remappedMetas
	 */
	public function __construct(
		private array $renamedIds,
		private array $remappedMetas,
		private int $schemaId
	){}

	public function getSchemaId() : int{ return $this->schemaId; }

	/**
	 * @return string[]
	 * @phpstan-return array<string, string>
	 */
	public function getRenamedIds() : array{ return $this->renamedIds; }

	/**
	 * @return string[][]
	 * @phpstan-return array<string, array<int, string>>
	 */
	public function getRemappedMetas() : array{ return $this->remappedMetas; }

	public function renameId(string $id) : ?string{
		return $this->renamedIds[mb_strtolower($id, 'US-ASCII')] ?? null;
	}

	public function remapMeta(string $id, int $meta) : ?string{
		return $this->remappedMetas[mb_strtolower($id, 'US-ASCII')][$meta] ?? null;
	}
}
