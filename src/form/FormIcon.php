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

namespace pocketmine\form;

/**
 * Image shown next to a simple form button, or as the icon of a custom form on the server settings screen.
 */
final class FormIcon implements \JsonSerializable{
	public const TYPE_PATH = "path";
	public const TYPE_URL = "url";

	private function __construct(
		private string $type,
		private string $data
	){}

	/**
	 * Texture from a loaded resource pack, e.g. "textures/items/apple".
	 */
	public static function path(string $path) : self{
		return new self(self::TYPE_PATH, $path);
	}

	/**
	 * Remote image, the client downloads it by itself.
	 */
	public static function url(string $url) : self{
		return new self(self::TYPE_URL, $url);
	}

	public function getType() : string{ return $this->type; }

	public function getData() : string{ return $this->data; }

	/**
	 * @return array<string, string>
	 */
	public function jsonSerialize() : array{
		return [
			"type" => $this->type,
			"data" => $this->data,
		];
	}
}
