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

namespace pocketmine\data\bedrock;

use pocketmine\data\bedrock\item\ItemTypeNames as Ids;
use pocketmine\item\PotterySherdType;
use pocketmine\utils\SingletonTrait;
use function array_key_exists;
use function spl_object_id;

final class PotterySherdTypeIdMap{
	use SingletonTrait;

	/**
	 * @var PotterySherdType[]
	 * @phpstan-var array<string, PotterySherdType>
	 */
	private array $idToEnum = [];
	/**
	 * @var string[]
	 * @phpstan-var array<int, string>
	 */
	private array $enumToId = [];

	private function __construct(){
		foreach(PotterySherdType::cases() as $case){
			$this->register(match($case){
				PotterySherdType::ANGLER => Ids::ANGLER_POTTERY_SHERD,
				PotterySherdType::ARCHER => Ids::ARCHER_POTTERY_SHERD,
				PotterySherdType::ARMS_UP => Ids::ARMS_UP_POTTERY_SHERD,
				PotterySherdType::BLADE => Ids::BLADE_POTTERY_SHERD,
				PotterySherdType::BREWER => Ids::BREWER_POTTERY_SHERD,
				PotterySherdType::BURN => Ids::BURN_POTTERY_SHERD,
				PotterySherdType::DANGER => Ids::DANGER_POTTERY_SHERD,
				PotterySherdType::EXPLORER => Ids::EXPLORER_POTTERY_SHERD,
				PotterySherdType::FLOW => Ids::FLOW_POTTERY_SHERD,
				PotterySherdType::FRIEND => Ids::FRIEND_POTTERY_SHERD,
				PotterySherdType::GUSTER => Ids::GUSTER_POTTERY_SHERD,
				PotterySherdType::HEART => Ids::HEART_POTTERY_SHERD,
				PotterySherdType::HEARTBREAK => Ids::HEARTBREAK_POTTERY_SHERD,
				PotterySherdType::HOWL => Ids::HOWL_POTTERY_SHERD,
				PotterySherdType::MINER => Ids::MINER_POTTERY_SHERD,
				PotterySherdType::MOURNER => Ids::MOURNER_POTTERY_SHERD,
				PotterySherdType::PLENTY => Ids::PLENTY_POTTERY_SHERD,
				PotterySherdType::PRIZE => Ids::PRIZE_POTTERY_SHERD,
				PotterySherdType::SCRAPE => Ids::SCRAPE_POTTERY_SHERD,
				PotterySherdType::SHEAF => Ids::SHEAF_POTTERY_SHERD,
				PotterySherdType::SHELTER => Ids::SHELTER_POTTERY_SHERD,
				PotterySherdType::SKULL => Ids::SKULL_POTTERY_SHERD,
				PotterySherdType::SNORT => Ids::SNORT_POTTERY_SHERD
			}, $case);
		}
	}

	public function register(string $stringId, PotterySherdType $type) : void{
		$this->idToEnum[$stringId] = $type;
		$this->enumToId[spl_object_id($type)] = $stringId;
	}

	public function fromId(string $stringId) : ?PotterySherdType{
		return $this->idToEnum[$stringId] ?? null;
	}

	public function toId(PotterySherdType $type) : string{
		$runtimeId = spl_object_id($type);
		if(!array_key_exists($runtimeId, $this->enumToId)){
			throw new \InvalidArgumentException("Pottery sherd type does not have a mapped ID");
		}
		return $this->enumToId[$runtimeId];
	}
}
