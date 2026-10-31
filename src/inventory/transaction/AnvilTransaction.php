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

use pocketmine\crafting\AnvilCraftResult;
use pocketmine\event\player\PlayerUseAnvilEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use function count;
use function min;

/**
 * Handles repairing, combining and renaming items in an anvil.
 */
class AnvilTransaction extends InventoryTransaction{

	public function __construct(
		Player $source,
		private readonly AnvilCraftResult $result
	){
		parent::__construct($source);
	}

	public function getResult() : AnvilCraftResult{
		return $this->result;
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

		if(($outputCount = count($outputs)) !== 1){
			throw new TransactionValidationException("Expected 1 output item, but received $outputCount");
		}
		if(!$outputs[0]->equalsExact($this->result->getOutput())){
			throw new TransactionValidationException("Invalid output item");
		}

		$expectedInputs = [$this->result->getInput()];
		if($this->result->getMaterialCost() > 0){
			$expectedInputs[] = $this->result->getMaterial();
		}
		$this->consumeExpectedInputs($expectedInputs, $inputs);

		if($this->source->hasFiniteResources()){
			$xpLevel = $this->source->getXpManager()->getXpLevel();
			$cost = $this->result->getXpCost();
			if($xpLevel < $cost){
				throw new TransactionValidationException("Player's XP level $xpLevel is less than the required XP level $cost");
			}
		}
	}

	/**
	 * @param Item[] $expected
	 * @param Item[] $inputs
	 * @phpstan-param list<Item> $expected
	 * @phpstan-param array<int, Item> $inputs
	 */
	private function consumeExpectedInputs(array $expected, array $inputs) : void{
		foreach($expected as $expectedItem){
			$remaining = $expectedItem->getCount();
			foreach($inputs as $i => $input){
				if($remaining <= 0){
					break;
				}
				if(!$input->canStackWith($expectedItem)){
					continue;
				}
				$taken = min($remaining, $input->getCount());
				$remaining -= $taken;
				$input->setCount($input->getCount() - $taken);
				if($input->getCount() <= 0){
					unset($inputs[$i]);
				}
			}
			if($remaining > 0){
				throw new TransactionValidationException("Missing $remaining x " . $expectedItem->getName() . " in anvil inputs");
			}
		}

		if(count($inputs) > 0){
			throw new TransactionValidationException("Unexpected extra items consumed by the anvil");
		}
	}

	public function execute() : void{
		parent::execute();

		if($this->source->hasFiniteResources()){
			$this->source->getXpManager()->subtractXpLevels($this->result->getXpCost());
		}
	}

	protected function callExecuteEvent() : bool{
		$event = new PlayerUseAnvilEvent(
			$this->source,
			$this,
			$this->result->getInput(),
			$this->result->getMaterial(),
			$this->result->getOutput(),
			$this->result->getXpCost()
		);
		$event->call();
		return !$event->isCancelled();
	}
}
