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

namespace pocketmine\data\bedrock\upgrade;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use function array_pop;
use function count;
use function is_int;
use function is_string;

final class CompoundTagEditHelper{
	/**
	 * @var Tag[]
	 * @phpstan-var list<Tag>
	 */
	private array $parentTags = [];
	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private array $tagNames = [];

	private CompoundTag $rootTag;

	private ?Tag $tag;

	public function __construct(CompoundTag $tag){
		$this->rootTag = $tag;
		$this->tag = $tag;
	}

	public function getRootTag() : CompoundTag{
		return $this->rootTag;
	}

	public function getCompoundTag() : CompoundTag{
		if(!$this->tag instanceof CompoundTag){
			throw new \LogicException("Current tag is not a compound tag");
		}
		return $this->tag;
	}

	public function getTag() : Tag{
		if($this->tag === null){
			throw new \LogicException("Current tag does not exist");
		}
		return $this->tag;
	}

	public function getIntValue() : int{
		$value = $this->getTag()->getValue();
		if(!is_int($value)){
			throw new \LogicException("Current tag does not have an integer value");
		}
		return $value;
	}

	public function getStringValue() : string{
		$value = $this->getTag()->getValue();
		if(!is_string($value)){
			throw new \LogicException("Current tag does not have a string value");
		}
		return $value;
	}

	public function getParent() : ?CompoundTag{
		if(count($this->parentTags) > 0){
			$tag = $this->parentTags[count($this->parentTags) - 1];
			if($tag instanceof CompoundTag){
				return $tag;
			}
		}
		return null;
	}

	public function canPopChild() : bool{
		return count($this->parentTags) > 0;
	}

	public function popChild() : void{
		if(count($this->parentTags) > 0){
			$this->tag = array_pop($this->parentTags);
			array_pop($this->tagNames);
		}
	}

	public function pushChild(string $name) : void{
		if(!$this->tag instanceof CompoundTag){
			throw new \LogicException("Tag is not of Compound type");
		}
		$this->parentTags[] = $this->tag;
		$this->tagNames[] = $name;
		$this->tag = $this->tag->getTag($name);
	}

	public function replaceWith(string $name, Tag $newTag) : void{
		$this->tag = $newTag;
		if(count($this->parentTags) === 0){
			if(!$newTag instanceof CompoundTag){
				throw new \LogicException("Cannot replace the root tag with a non-compound tag");
			}
			$this->rootTag = $newTag;
			return;
		}
		$parent = $this->parentTags[count($this->parentTags) - 1];
		if($parent instanceof CompoundTag){
			$oldName = array_pop($this->tagNames);
			if($oldName !== null){
				$parent->removeTag($oldName);
			}
			$parent->setTag($name, $newTag);
			$this->tagNames[] = $name;
		}
	}
}
