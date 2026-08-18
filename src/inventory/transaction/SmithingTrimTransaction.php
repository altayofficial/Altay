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

namespace pocketmine\inventory\transaction;

use pocketmine\data\bedrock\item\ArmorTrimRegistry;
use pocketmine\item\Armor;
use pocketmine\item\ArmorTrim;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use function count;

/**
 * Handles applying an armor trim (pattern + material) to an armor piece in a smithing table. This can't be expressed
 * as a normal {@link \pocketmine\crafting\CraftingRecipe}, since the result depends on which specific pattern and
 * material items were provided, not just their presence.
 */
class SmithingTrimTransaction extends InventoryTransaction{

	private ?Armor $equipment = null;
	private ?Item $output = null;

	public function __construct(Player $source){
		parent::__construct($source);
	}

	public function validate() : void{
		if(count($this->actions) < 1){
			throw new TransactionValidationException("Transaction must have at least one action to be executable");
		}

		/** @var Item[] $outputs */
		$outputs = [];
		/** @var Item[] $inputs */
		$inputs = [];
		$this->matchItems($outputs, $inputs);

		if(count($inputs) !== 3){
			throw new TransactionValidationException("Expected exactly 3 input items (equipment, material and template), got " . count($inputs));
		}

		$registry = ArmorTrimRegistry::getInstance();
		$patternId = null;
		$materialId = null;
		foreach($inputs as $input){
			if($input instanceof Armor){
				if($this->equipment !== null){
					throw new TransactionValidationException("Received more than 1 item to apply a trim to");
				}
				$this->equipment = $input;
				continue;
			}
			if(($foundPattern = $registry->getPatternId($input)) !== null){
				$patternId = $foundPattern;
				continue;
			}
			if(($foundMaterial = $registry->getMaterialId($input)) !== null){
				$materialId = $foundMaterial;
				continue;
			}
			throw new TransactionValidationException("Item $input is not a valid trim equipment, template or material");
		}

		if($this->equipment === null || $patternId === null || $materialId === null){
			throw new TransactionValidationException("Missing equipment, template or material for armor trim");
		}

		if(($outputCount = count($outputs)) !== 1){
			throw new TransactionValidationException("Expected 1 output item, but received $outputCount");
		}

		$expected = clone $this->equipment;
		$expected->setTrim(new ArmorTrim($patternId, $materialId));
		if(!$outputs[0]->equalsExact($expected)){
			throw new TransactionValidationException("Invalid output item");
		}
		$this->output = $outputs[0];
	}

	protected function callExecuteEvent() : bool{
		if($this->equipment === null || $this->output === null){
			throw new AssumptionFailedError("Expected that equipment and output are not null before executing the event");
		}

		return true;
	}
}
