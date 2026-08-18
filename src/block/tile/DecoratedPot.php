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

namespace pocketmine\block\tile;

use pocketmine\block\utils\PotDecorations;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\PotterySherdTypeIdMap;
use pocketmine\inventory\SimpleInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\World;

class DecoratedPot extends Spawnable implements Container{
	use ContainerTrait;

	public const TAG_SHERDS = "sherds";

	private SimpleInventory $inventory;

	private PotDecorations $decorations;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$this->inventory = new SimpleInventory(1);
		$this->decorations = new PotDecorations();
	}

	public function getInventory() : SimpleInventory{
		return $this->inventory;
	}

	public function getRealInventory() : SimpleInventory{
		return $this->inventory;
	}

	public function getDecorations() : PotDecorations{
		return $this->decorations;
	}

	public function setDecorations(PotDecorations $decorations) : void{
		$this->decorations = $decorations;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadItems($nbt);

		$idMap = PotterySherdTypeIdMap::getInstance();
		$faces = [];
		$sherds = $nbt->getListTag(self::TAG_SHERDS, StringTag::class);
		if($sherds !== null){
			foreach($sherds as $sherd){
				//unknown IDs (including plain bricks) leave the face undecorated
				$faces[] = $idMap->fromId($sherd->getValue());
			}
		}

		$this->decorations = PotDecorations::fromArray($faces);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveItems($nbt);
		$this->writeDecorations($nbt);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$this->writeDecorations($nbt);
	}

	private function writeDecorations(CompoundTag $nbt) : void{
		$idMap = PotterySherdTypeIdMap::getInstance();
		$sherds = [];
		//the client expects all four faces to be present, with plain bricks standing in for undecorated ones
		foreach($this->decorations->toArray() as $face){
			$sherds[] = new StringTag($face === null ? ItemTypeNames::BRICK : $idMap->toId($face));
		}

		$nbt->setTag(self::TAG_SHERDS, new ListTag($sherds, NBT::TAG_String));
	}
}
