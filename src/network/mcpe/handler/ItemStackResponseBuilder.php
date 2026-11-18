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

namespace pocketmine\network\mcpe\handler;

use pocketmine\inventory\Inventory;
use pocketmine\item\Durable;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseContainerInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseSlotInfo;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;

final class ItemStackResponseBuilder{

	/**
	 * @var FullContainerName[] container key => container name
	 * @phpstan-var array<string, FullContainerName>
	 */
	private array $changedContainers = [];
	/**
	 * @var int[][] container key => slot IDs
	 * @phpstan-var array<string, array<int, int>>
	 */
	private array $changedSlots = [];

	public function __construct(
		private int $requestId,
		private InventoryManager $inventoryManager
	){}

	public function addSlot(FullContainerName $containerName, int $slotId) : void{
		//bundles all share the same container ID, so the dynamic ID has to be part of the key to tell them apart
		$key = $containerName->getContainerId() . ":" . ($containerName->getDynamicId() ?? "");
		$this->changedContainers[$key] = $containerName;
		$this->changedSlots[$key][$slotId] = $slotId;
	}

	/**
	 * @phpstan-return array{Inventory, int}
	 */
	private function getInventoryAndSlot(FullContainerName $containerName, int $slotId) : ?array{
		$containerInterfaceId = $containerName->getContainerId();
		if($containerInterfaceId === ContainerUIIds::DYNAMIC){
			$bundleId = $containerName->getDynamicId();
			$windowAndSlot = $bundleId !== null ? $this->inventoryManager->locateBundleSlot($bundleId, $slotId) : null;
		}else{
			[$windowId, $slotId] = ItemStackContainerIdTranslator::translate($containerInterfaceId, $this->inventoryManager->getCurrentWindowId(), $slotId);
			$windowAndSlot = $this->inventoryManager->locateWindowAndSlot($windowId, $slotId);
		}
		if($windowAndSlot === null){
			return null;
		}
		[$inventory, $slot] = $windowAndSlot;
		if(!$inventory->slotExists($slot)){
			return null;
		}

		return [$inventory, $slot];
	}

	public function build() : ItemStackResponse{
		$responseInfosByContainer = [];
		foreach(Utils::stringifyKeys($this->changedSlots) as $key => $slotIds){
			$containerName = $this->changedContainers[$key];
			if($containerName->getContainerId() === ContainerUIIds::CREATED_OUTPUT){
				continue;
			}
			foreach($slotIds as $slotId){
				$inventoryAndSlot = $this->getInventoryAndSlot($containerName, $slotId);
				if($inventoryAndSlot === null){
					//a plugin may have closed the inventory during an event, or the slot may have been invalid
					continue;
				}
				[$inventory, $slot] = $inventoryAndSlot;

				$itemStackInfo = $this->inventoryManager->getItemStackInfo($inventory, $slot);
				if($itemStackInfo === null){
					throw new AssumptionFailedError("ItemStackInfo should never be null for an open inventory");
				}
				$item = $inventory->getItem($slot);

				$responseInfosByContainer[$key][] = new ItemStackResponseSlotInfo(
					$slotId,
					$slotId,
					$item->getCount(),
					$itemStackInfo->getStackId(),
					$item->getCustomName(),
					$item->getCustomName(),
					$item instanceof Durable ? $item->getDamage() : 0,
				);
			}
		}

		$responseContainerInfos = [];
		foreach(Utils::stringifyKeys($responseInfosByContainer) as $key => $responseInfos){
			$responseContainerInfos[] = new ItemStackResponseContainerInfo($this->changedContainers[$key], $responseInfos);
		}

		return new ItemStackResponse(ItemStackResponse::RESULT_OK, $this->requestId, $responseContainerInfos);
	}
}
