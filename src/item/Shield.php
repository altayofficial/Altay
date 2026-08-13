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

namespace pocketmine\item;

use pocketmine\block\utils\BannerPatternLayer;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\BannerPatternTypeIdMap;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use function count;

class Shield extends Durable{
	public const TAG_BASE = "Base";
	public const TAG_PATTERNS = Banner::TAG_PATTERNS;
	public const TAG_PATTERN_COLOR = Banner::TAG_PATTERN_COLOR;
	public const TAG_PATTERN_NAME = Banner::TAG_PATTERN_NAME;

	private ?DyeColor $baseColor = null;

	/**
	 * @var BannerPatternLayer[]
	 * @phpstan-var list<BannerPatternLayer>
	 */
	private array $patterns = [];

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getMaxDurability() : int{
		return 336;
	}

	public function hasBannerPattern() : bool{
		return $this->baseColor !== null || count($this->patterns) > 0;
	}

	public function getBaseColor() : ?DyeColor{
		return $this->baseColor;
	}

	/**
	 * @return BannerPatternLayer[]
	 * @phpstan-return list<BannerPatternLayer>
	 */
	public function getPatterns() : array{
		return $this->patterns;
	}

	public function getBanner() : ?Banner{
		if(!$this->hasBannerPattern()){
			return null;
		}

		return VanillaItems::BANNER()
			->setColor($this->baseColor ?? DyeColor::BLACK)
			->setPatterns($this->patterns);
	}

	/**
	 * @return $this
	 */
	public function setBanner(?Banner $banner) : self{
		if($banner === null){
			$this->baseColor = null;
			$this->patterns = [];
		}else{
			$this->baseColor = $banner->getColor();
			$this->patterns = $banner->getPatterns();
		}

		return $this;
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$colorIdMap = DyeColorIdMap::getInstance();
		$patternIdMap = BannerPatternTypeIdMap::getInstance();

		$baseColorTag = $tag->getTag(self::TAG_BASE);
		$this->baseColor = $baseColorTag === null ? null : $colorIdMap->fromInvertedId($tag->getInt(self::TAG_BASE));

		$this->patterns = [];
		$patterns = $tag->getListTag(self::TAG_PATTERNS, CompoundTag::class);
		if($patterns !== null){
			foreach($patterns as $t){
				$patternColor = $colorIdMap->fromInvertedId($t->getInt(self::TAG_PATTERN_COLOR)) ?? DyeColor::BLACK;
				$patternType = $patternIdMap->fromId($t->getString(self::TAG_PATTERN_NAME));
				if($patternType === null){
					continue;
				}
				$this->patterns[] = new BannerPatternLayer($patternType, $patternColor);
			}
		}
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($this->baseColor !== null){
			$tag->setInt(self::TAG_BASE, DyeColorIdMap::getInstance()->toInvertedId($this->baseColor));
		}else{
			$tag->removeTag(self::TAG_BASE);
		}

		if(count($this->patterns) > 0){
			$patterns = new ListTag();
			$colorIdMap = DyeColorIdMap::getInstance();
			$patternIdMap = BannerPatternTypeIdMap::getInstance();
			foreach($this->patterns as $pattern){
				$patterns->push(CompoundTag::create()
					->setString(self::TAG_PATTERN_NAME, $patternIdMap->toId($pattern->getType()))
					->setInt(self::TAG_PATTERN_COLOR, $colorIdMap->toInvertedId($pattern->getColor()))
				);
			}

			$tag->setTag(self::TAG_PATTERNS, $patterns);
		}else{
			$tag->removeTag(self::TAG_PATTERNS);
		}
	}
}
