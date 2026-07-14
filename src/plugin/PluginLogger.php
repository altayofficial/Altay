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

namespace pocketmine\plugin;

use function spl_object_id;

/**
 * @phpstan-import-type LoggerAttachment from \AttachableLogger
 */
class PluginLogger extends \PrefixedLogger implements \AttachableLogger{

	/**
	 * @var \Closure[]
	 * @phpstan-var LoggerAttachment[]
	 */
	private array $attachments = [];

	/**
	 * @phpstan-param LoggerAttachment $attachment
	 */
	public function addAttachment(\Closure $attachment){
		$this->attachments[spl_object_id($attachment)] = $attachment;
	}

	/**
	 * @phpstan-param LoggerAttachment $attachment
	 */
	public function removeAttachment(\Closure $attachment){
		unset($this->attachments[spl_object_id($attachment)]);
	}

	public function removeAttachments(){
		$this->attachments = [];
	}

	public function getAttachments(){
		return $this->attachments;
	}

	public function log($level, $message){
		parent::log($level, $message);
		foreach($this->attachments as $attachment){
			$attachment($level, $message);
		}
	}
}
