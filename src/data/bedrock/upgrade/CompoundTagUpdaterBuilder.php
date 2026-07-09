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

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Tag;
use function preg_match;

final class CompoundTagUpdaterBuilder{

	public function __construct(
		private CompoundTagUpdater $updater
	){}

	public function addByte(string $name, int $value) : self{
		$this->updater->addFilter(fn(CompoundTagEditHelper $helper) => $helper->getTag() instanceof CompoundTag);
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name, $value) : void{ $helper->getCompoundTag()->setTag($name, new ByteTag($value)); });
		return $this;
	}

	public function addInt(string $name, int $value) : self{
		$this->updater->addFilter(fn(CompoundTagEditHelper $helper) => $helper->getTag() instanceof CompoundTag);
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name, $value) : void{ $helper->getCompoundTag()->setTag($name, new IntTag($value)); });
		return $this;
	}

	public function addCompound(string $name) : self{
		$this->updater->addFilter(fn(CompoundTagEditHelper $helper) => $helper->getTag() instanceof CompoundTag);
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name) : void{ $helper->getCompoundTag()->setTag($name, new CompoundTag()); });
		return $this;
	}

	public function tryAdd(string $name, Tag $value) : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($name) : bool{
			$tag = $helper->getTag();
			return $tag instanceof CompoundTag && $tag->getTag($name) === null;
		});
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name, $value) : void{ $helper->getCompoundTag()->setTag($name, clone $value); });
		return $this;
	}

	/**
	 * @phpstan-param \Closure(CompoundTagEditHelper) : void $function
	 */
	public function edit(string $name, \Closure $function) : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($name) : bool{
			$tag = $helper->getTag();
			return $tag instanceof CompoundTag && $tag->getTag($name) !== null;
		});
		$this->updater->addUpdate(fn(CompoundTagEditHelper $helper) => $helper->pushChild($name));
		$this->updater->addUpdate($function);
		$this->updater->addUpdate(fn(CompoundTagEditHelper $helper) => $helper->popChild());
		return $this;
	}

	public function regex(string $name, string $regex) : self{
		return $this->match($name, $regex, true);
	}

	public function match(string $name, string $match, bool $regex = false) : self{
		$pattern = $regex ? '/^(?:' . $match . ')$/' : null;

		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($name, $match, $pattern) : bool{
			$tag = $helper->getTag();
			if(!$tag instanceof CompoundTag){
				return false;
			}
			$matchTag = $tag->getTag($name);
			if($matchTag === null){
				return false;
			}

			if($match === ""){
				return true;
			}

			$value = CompoundTagUpdater::getTagValue($matchTag);
			if($pattern !== null){
				return preg_match($pattern, $value) === 1;
			}
			return $match === $value;
		});
		return $this;
	}

	public function popVisit() : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) : bool{
			if($helper->canPopChild()){
				$helper->popChild();
				return true;
			}
			return false;
		});
		$this->updater->addUpdate(fn(CompoundTagEditHelper $helper) => $helper->popChild());
		return $this;
	}

	public function remove(string $name) : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($name) : bool{
			$tag = $helper->getTag();
			return $tag instanceof CompoundTag && $tag->getTag($name) !== null;
		});
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name) : void{ $helper->getCompoundTag()->removeTag($name); });
		return $this;
	}

	public function rename(string $from, string $to) : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($from) : bool{
			$tag = $helper->getTag();
			return $tag instanceof CompoundTag && $tag->getTag($from) !== null;
		});
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($from, $to) : void{
			$tag = $helper->getCompoundTag();
			$value = $tag->getTag($from);
			if($value !== null){
				$tag->removeTag($from);
				$tag->setTag($to, $value);
			}
		});
		return $this;
	}

	/**
	 * @phpstan-param \Closure(CompoundTagEditHelper) : void $function
	 */
	public function tryEdit(string $name, \Closure $function) : self{
		$this->updater->addUpdate(function(CompoundTagEditHelper $helper) use ($name, $function) : void{
			$tag = $helper->getTag();
			if($tag instanceof CompoundTag && $tag->getTag($name) !== null){
				$helper->pushChild($name);
				$function($helper);
				$helper->popChild();
			}
		});
		return $this;
	}

	public function visit(string $name) : self{
		$this->updater->addFilter(function(CompoundTagEditHelper $helper) use ($name) : bool{
			$tag = $helper->getTag();
			if($tag instanceof CompoundTag && $tag->getTag($name) !== null){
				$helper->pushChild($name);
				return true;
			}
			return false;
		});
		$this->updater->addUpdate(fn(CompoundTagEditHelper $helper) => $helper->pushChild($name));
		return $this;
	}

	public function build() : CompoundTagUpdater{
		return $this->updater;
	}
}
