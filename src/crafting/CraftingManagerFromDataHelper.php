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

namespace pocketmine\crafting;

use pocketmine\crafting\json\ItemStackData;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function base64_decode;
use function count;
use function get_debug_type;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function json_decode;

final class CraftingManagerFromDataHelper{

	private const NETWORK_RECIPE_TYPE_SHAPELESS = 0;
	private const NETWORK_RECIPE_TYPE_SHAPED = 1;
	private const NETWORK_RECIPE_TYPE_MULTI = 4;
	private const NETWORK_RECIPE_TYPE_USER_DATA_SHAPELESS = 5;
	private const NETWORK_RECIPE_TYPE_SMITHING_TRANSFORM = 8;
	private const NETWORK_RECIPE_TYPE_SMITHING_TRIM = 9;

	private const NETWORK_INGREDIENT_WILDCARD_META = 0x7fff;

	public static function deserializeItemStack(ItemStackData $data) : ?Item{
		//count, name, block_name, block_states, meta, nbt, can_place_on, can_destroy
		$blockStatesTag = null;
		if(isset($data->block_states)){
			$blockStatesTag = (new LittleEndianNbtSerializer())
				->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($data->block_states, true)))
				->mustGetCompoundTag();
		}
		$nbt = !isset($data->nbt) ? null : (new LittleEndianNbtSerializer())
			->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($data->nbt, true)))
			->mustGetCompoundTag();

		return self::deserializeItemStackFromFields(
			$data->name,
			$data->meta ?? null,
			$data->count ?? null,
			$blockStatesTag,
			$nbt,
			$data->can_place_on ?? [],
			$data->can_destroy ?? []
		);
	}

	/**
	 * Deserializes an item stack described by string ID + meta (aux value), the way network recipe and creative data
	 * describes items. For blockitems without explicit blockstates, the blockstate is looked up from the network
	 * block palette using the meta value.
	 *
	 * @param string[] $canPlaceOn
	 * @param string[] $canDestroy
	 */
	public static function deserializeItemStackFromFields(string $name, ?int $meta, ?int $count, ?CompoundTag $blockStatesTag, ?CompoundTag $nbt, array $canPlaceOn = [], array $canDestroy = []) : ?Item{
		$count ??= 1;

		$blockName = BlockItemIdMap::getInstance()->lookupBlockId($name);
		if($blockName !== null){
			if($blockStatesTag !== null){
				$blockStateData = BlockStateData::current($blockName, $blockStatesTag->getValue());
			}else{
				//no explicit blockstates - look up the default (or meta-mapped) state from the network block palette
				$dictionary = TypeConverter::getInstance()->getBlockTranslator()->getBlockStateDictionary();
				$stateId = $dictionary->lookupStateIdFromIdMeta($blockName, $meta ?? 0) ?? $dictionary->lookupStateIdFromIdMeta($blockName, 0);
				if($stateId === null){
					//unknown block
					return null;
				}
				$blockStateData = $dictionary->generateDataFromStateId($stateId);
			}
			$meta = 0;
		}else{
			$blockStateData = null;
		}

		$itemStackData = new SavedItemStackData(
			new SavedItemData(
				$name,
				$meta ?? 0,
				$blockStateData,
				$nbt
			),
			$count,
			null,
			null,
			$canPlaceOn,
			$canDestroy,
		);

		try{
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch(ItemTypeDeserializeException){
			//probably unknown item
			return null;
		}
	}

	/**
	 * @param mixed[] $data
	 */
	private static function deserializeNetworkIngredient(array $data) : ?RecipeIngredient{
		$type = $data["type"] ?? null;
		if(!is_string($type)){
			throw new SavedDataLoadingException("Recipe ingredient should have a string type");
		}
		$count = $data["count"] ?? 1;
		if($count !== 1){
			//every case we've seen so far where this isn't the case, it's been a bug and the count was ignored anyway
			throw new SavedDataLoadingException("Recipe inputs should have a count of exactly 1");
		}

		if($type === "item_tag"){
			if(!isset($data["itemTag"]) || !is_string($data["itemTag"])){
				throw new SavedDataLoadingException("item_tag ingredient should have a string itemTag");
			}
			return new TagWildcardRecipeIngredient($data["itemTag"]);
		}

		if($type === "complex_alias"){
			//not enough information to resolve these
			return null;
		}

		if($type !== "default"){
			throw new SavedDataLoadingException("Unsupported recipe ingredient type \"$type\"");
		}

		if(!isset($data["itemId"]) || !is_string($data["itemId"])){
			throw new SavedDataLoadingException("default ingredient should have a string itemId");
		}
		$meta = $data["auxValue"] ?? 0;
		if(!is_int($meta)){
			throw new SavedDataLoadingException("default ingredient auxValue should be an int");
		}

		if($meta === self::NETWORK_INGREDIENT_WILDCARD_META){
			//this could be an unimplemented item, but it doesn't really matter, since the item shouldn't be able to
			//be obtained anyway - filtering unknown items is only really important for outputs, to prevent players
			//obtaining them
			return new MetaWildcardRecipeIngredient($data["itemId"]);
		}

		$itemStack = self::deserializeItemStackFromFields($data["itemId"], $meta, 1, null, null);
		if($itemStack === null){
			//probably unknown item
			return null;
		}
		return new ExactRecipeIngredient($itemStack);
	}

	/**
	 * @param mixed[] $data
	 */
	private static function deserializeNetworkItemStack(array $data) : ?Item{
		if(!isset($data["id"]) || !is_string($data["id"])){
			throw new SavedDataLoadingException("Recipe output should have a string id");
		}
		$nbt = null;
		if(isset($data["nbt_b64"])){
			if(!is_string($data["nbt_b64"])){
				throw new SavedDataLoadingException("nbt_b64 should be a string");
			}
			$nbt = (new LittleEndianNbtSerializer())
				->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($data["nbt_b64"], true)))
				->mustGetCompoundTag();
		}

		$damage = $data["damage"] ?? null;
		$count = $data["count"] ?? null;
		if(($damage !== null && !is_int($damage)) || ($count !== null && !is_int($count))){
			throw new SavedDataLoadingException("damage and count should be ints");
		}

		return self::deserializeItemStackFromFields(
			$data["id"],
			$damage,
			$count,
			null,
			$nbt
		);
	}

	/**
	 * @param mixed[] $recipe
	 */
	private static function loadShapelessRecipe(CraftingManager $manager, array $recipe) : void{
		$recipeType = match($recipe["block"] ?? null){
			"crafting_table" => ShapelessRecipeType::CRAFTING,
			"stonecutter" => ShapelessRecipeType::STONECUTTER,
			"smithing_table" => ShapelessRecipeType::SMITHING,
			"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
			"furnace" => FurnaceType::FURNACE,
			"blast_furnace" => FurnaceType::BLAST_FURNACE,
			"smoker" => FurnaceType::SMOKER,
			"campfire" => FurnaceType::CAMPFIRE,
			"soul_campfire" => FurnaceType::SOUL_CAMPFIRE,
			default => null
		};
		if($recipeType === null){
			return;
		}
		if(!isset($recipe["input"]) || !is_array($recipe["input"]) || !isset($recipe["output"]) || !is_array($recipe["output"])){
			throw new SavedDataLoadingException("Shapeless recipe should have input and output lists");
		}
		$inputs = [];
		foreach($recipe["input"] as $inputData){
			if(!is_array($inputData)){
				throw new SavedDataLoadingException("Shapeless recipe input should be an object");
			}
			$input = self::deserializeNetworkIngredient($inputData);
			if($input === null){ //unknown or unsupported input
				return;
			}
			$inputs[] = $input;
		}
		$outputs = [];
		foreach($recipe["output"] as $outputData){
			if(!is_array($outputData)){
				throw new SavedDataLoadingException("Shapeless recipe output should be an object");
			}
			$output = self::deserializeNetworkItemStack($outputData);
			if($output === null){ //unknown output item
				return;
			}
			$outputs[] = $output;
		}
		//TODO: check unlocking requirements - our current system doesn't support this

		if($recipeType instanceof FurnaceType){
			if(count($inputs) !== 1 || count($outputs) !== 1){
				throw new SavedDataLoadingException("Furnace recipes must have exactly 1 input and 1 output");
			}

			$manager->getFurnaceRecipeManager($recipeType)->register(new FurnaceRecipe(
				$outputs[0],
				$inputs[0]
			));
		}else{
			$manager->registerShapelessRecipe(new ShapelessRecipe(
				$inputs,
				$outputs,
				$recipeType
			));
		}
	}

	/**
	 * @param mixed[] $recipe
	 */
	private static function loadShapedRecipe(CraftingManager $manager, array $recipe) : void{
		if(($recipe["block"] ?? null) !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
			return;
		}
		if(!isset($recipe["input"]) || !is_array($recipe["input"]) || !isset($recipe["output"]) || !is_array($recipe["output"]) || !isset($recipe["shape"]) || !is_array($recipe["shape"])){
			throw new SavedDataLoadingException("Shaped recipe should have input, output and shape");
		}
		$inputs = [];
		foreach(Utils::promoteKeys($recipe["input"]) as $symbol => $inputData){
			if(!is_string($symbol) || !is_array($inputData)){
				throw new SavedDataLoadingException("Shaped recipe input should be a map of symbol => ingredient");
			}
			$input = self::deserializeNetworkIngredient($inputData);
			if($input === null){ //unknown or unsupported input
				return;
			}
			$inputs[$symbol] = $input;
		}
		$outputs = [];
		foreach($recipe["output"] as $outputData){
			if(!is_array($outputData)){
				throw new SavedDataLoadingException("Shaped recipe output should be an object");
			}
			$output = self::deserializeNetworkItemStack($outputData);
			if($output === null){ //unknown output item
				return;
			}
			$outputs[] = $output;
		}
		$shape = [];
		foreach($recipe["shape"] as $row){
			if(!is_string($row)){
				throw new SavedDataLoadingException("Shaped recipe shape should be a list of strings");
			}
			$shape[] = $row;
		}
		//TODO: check unlocking requirements - our current system doesn't support this
		$manager->registerShapedRecipe(new ShapedRecipe(
			$shape,
			$inputs,
			$outputs
		));
	}

	/**
	 * @return mixed[]
	 *
	 * @phpstan-template TData of object
	 * @phpstan-param class-string<TData> $modelCLass
	 * @phpstan-return list<TData>
	 */
	public static function loadJsonArrayOfObjectsFile(string $filePath, string $modelCLass) : array{
		$recipes = json_decode(Filesystem::fileGetContents($filePath));
		if(!is_array($recipes)){
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($recipes));
		}

		$mapper = new \JsonMapper();
		$mapper->bStrictObjectTypeChecking = false; //to allow hydrating ItemStackData - since this is only used for offline data it's safe
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bExceptionOnMissingData = true;

		$result = [];
		foreach(Utils::promoteKeys($recipes) as $i => $item){
			if(!is_object($item)){
				throw new SavedDataLoadingException("Invalid entry at index $i: expected object, got " . get_debug_type($item));
			}
			try{
				$result[] = $mapper->map($item, (new \ReflectionClass($modelCLass))->newInstanceWithoutConstructor());
			}catch(\JsonMapper_Exception $e){
				throw new SavedDataLoadingException("Invalid entry at index $i: " . $e->getMessage(), 0, $e);
			}
		}
		return $result;
	}

	/**
	 * Builds a CraftingManager from a bedrock-network-data recipes.json file.
	 */
	public static function make(string $filePath) : CraftingManager{
		$data = json_decode(Filesystem::fileGetContents($filePath), true);
		if(
			!is_array($data) ||
			!isset($data["recipes"], $data["potionMixes"], $data["containerMixes"]) ||
			!is_array($data["recipes"]) || !is_array($data["potionMixes"]) || !is_array($data["containerMixes"])
		){
			throw new SavedDataLoadingException("$filePath should contain recipes, potionMixes and containerMixes lists");
		}

		$result = new CraftingManager();

		foreach(Utils::promoteKeys($data["recipes"]) as $i => $recipe){
			if(!is_array($recipe) || !isset($recipe["type"]) || !is_int($recipe["type"])){
				throw new SavedDataLoadingException("Invalid recipe at index $i: expected object with int type");
			}
			try{
				switch($recipe["type"]){
					case self::NETWORK_RECIPE_TYPE_SHAPELESS:
					case self::NETWORK_RECIPE_TYPE_USER_DATA_SHAPELESS:
						self::loadShapelessRecipe($result, $recipe);
						break;
					case self::NETWORK_RECIPE_TYPE_SHAPED:
						self::loadShapedRecipe($result, $recipe);
						break;
					case self::NETWORK_RECIPE_TYPE_MULTI:
					case self::NETWORK_RECIPE_TYPE_SMITHING_TRANSFORM:
					case self::NETWORK_RECIPE_TYPE_SMITHING_TRIM:
						//TODO: not supported by the crafting system yet
						break;
				}
			}catch(SavedDataLoadingException $e){
				throw new SavedDataLoadingException("Invalid recipe at index $i: " . $e->getMessage(), 0, $e);
			}
		}

		foreach(Utils::promoteKeys($data["potionMixes"]) as $i => $mix){
			if(
				!is_array($mix) ||
				!isset($mix["inputId"], $mix["inputMeta"], $mix["reagentId"], $mix["reagentMeta"], $mix["outputId"], $mix["outputMeta"]) ||
				!is_string($mix["inputId"]) || !is_int($mix["inputMeta"]) ||
				!is_string($mix["reagentId"]) || !is_int($mix["reagentMeta"]) ||
				!is_string($mix["outputId"]) || !is_int($mix["outputMeta"])
			){
				throw new SavedDataLoadingException("Invalid potion mix at index $i");
			}
			$input = self::deserializeItemStackFromFields($mix["inputId"], $mix["inputMeta"], 1, null, null);
			$reagent = self::deserializeItemStackFromFields($mix["reagentId"], $mix["reagentMeta"], 1, null, null);
			$output = self::deserializeItemStackFromFields($mix["outputId"], $mix["outputMeta"], 1, null, null);
			if($input === null || $reagent === null || $output === null){
				continue;
			}
			$result->registerPotionTypeRecipe(new PotionTypeRecipe(
				new ExactRecipeIngredient($input),
				new ExactRecipeIngredient($reagent),
				$output
			));
		}

		foreach(Utils::promoteKeys($data["containerMixes"]) as $i => $mix){
			if(
				!is_array($mix) ||
				!isset($mix["inputId"], $mix["reagentId"], $mix["outputId"]) ||
				!is_string($mix["inputId"]) || !is_string($mix["reagentId"]) || !is_string($mix["outputId"])
			){
				throw new SavedDataLoadingException("Invalid container mix at index $i");
			}
			$reagent = self::deserializeItemStackFromFields($mix["reagentId"], null, 1, null, null);
			//TODO: this is a really awful way to just check if an ID is recognized ...
			if(
				$reagent === null ||
				self::deserializeItemStackFromFields($mix["inputId"], null, 1, null, null) === null ||
				self::deserializeItemStackFromFields($mix["outputId"], null, 1, null, null) === null
			){
				//unknown item
				continue;
			}
			$result->registerPotionContainerChangeRecipe(new PotionContainerChangeRecipe(
				$mix["inputId"],
				new ExactRecipeIngredient($reagent),
				$mix["outputId"]
			));
		}

		return $result;
	}
}
