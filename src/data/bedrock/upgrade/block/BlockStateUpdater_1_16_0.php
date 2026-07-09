<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\upgrade\block;

use pocketmine\data\bedrock\upgrade\CompoundTagEditHelper;
use pocketmine\data\bedrock\upgrade\CompoundTagUpdaterContext;
use pocketmine\data\bedrock\upgrade\Updater;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;

final class BlockStateUpdater_1_16_0 implements Updater{
	use SingletonTrait;

	private static function convertFacingDirectionToDirection(int $facingDirection) : int{
		switch($facingDirection){
			case 2:
				return 2;
			case 3:
			default:
				return 0;
			case 4:
				return 1;
			case 5:
				return 3;
		}
	}

	public function registerUpdaters(CompoundTagUpdaterContext $context) : void{
		$context->addUpdater(1, 16, 0)
			->match("name", "jigsaw")
			->visit("states")
			->tryAdd("rotation", new IntTag(0));

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:blue_fire")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("soul_fire"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:blue_nether_wart_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("warped_wart_block"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:shroomlight_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:shroomlight"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:weeping_vines_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:weeping_vines"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:basalt_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:basalt"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:polished_basalt_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:polished_basalt"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:soul_soil_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:soul_soil"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:target_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:target"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:crimson_trap_door")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:crimsom_trapdoor"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:lodestone_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:lodestone"));
			});

		$context->addUpdater(1, 16, 0)
			->match("name", "minecraft:twisted_vines_block")
			->edit("name", function(CompoundTagEditHelper $helper) : void{
				$helper->replaceWith("name", new StringTag("minecraft:twisted_vines"));
			});

		// This is not a vanilla state updater. In vanilla 1.16, the invalid block state is updated when the chunk is
		// loaded in so it can generate the connection data however the state set below should never occur naturally.
		// Checking for this block state instead means we don't have to break our loading system in order to support it.
		$this->addLegacyWallUpdater($context, "minecraft:.+_wall");
		$this->addLegacyWallUpdater($context, "minecraft:border_block");

		$this->addWallUpdater($context, "minecraft:blackstone_wall");
		$this->addWallUpdater($context, "minecraft:polished_blackstone_brick_wall");
		$this->addWallUpdater($context, "minecraft:polished_blackstone_wall");

		$this->addBeeHiveUpdater($context, "minecraft:beehive");
		$this->addBeeHiveUpdater($context, "minecraft:bee_nest");

		$this->addRequiredValueUpdater($context, "minecraft:pumpkin_stem", "facing_direction", new IntTag(0));
		$this->addRequiredValueUpdater($context, "minecraft:melon_stem", "facing_direction", new IntTag(0));
	}

	private function addLegacyWallUpdater(CompoundTagUpdaterContext $context, string $name) : void{
		$context->addUpdater(1, 16, 0)
			->regex("name", $name)
			->tryEdit("states", function(CompoundTagEditHelper $helper) : void{
				$states = $helper->getCompoundTag();
				$states->setTag("wall_post_bit", new ByteTag(0));
				$states->setTag("wall_connection_type_north", new StringTag("none"));
				$states->setTag("wall_connection_type_east", new StringTag("none"));
				$states->setTag("wall_connection_type_south", new StringTag("none"));
				$states->setTag("wall_connection_type_west", new StringTag("none"));
			});
	}

	private function addWallUpdater(CompoundTagUpdaterContext $context, string $name) : void{
		$context->addUpdater(1, 16, 0)
			->match("name", $name)
			->visit("states")
			->remove("wall_block_type");
	}

	private function addBeeHiveUpdater(CompoundTagUpdaterContext $context, string $name) : void{
		$context->addUpdater(1, 16, 0)
			->match("name", $name)
			->visit("states")
			->edit("facing_direction", function(CompoundTagEditHelper $helper) : void{
				$facingDirection = $helper->getIntValue();
				$helper->replaceWith("direction", new IntTag(self::convertFacingDirectionToDirection($facingDirection)));
			});
	}

	private function addRequiredValueUpdater(CompoundTagUpdaterContext $context, string $name, string $state, IntTag $value) : void{
		$context->addUpdater(1, 16, 0)
			->match("name", $name)
			->visit("states")
			->tryAdd($state, $value);
	}
}
