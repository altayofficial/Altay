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

namespace pocketmine\world\generator\executor;

use pocketmine\scheduler\AsyncTask;

class AsyncGeneratorRegisterTask extends AsyncTask{

	public function __construct(
		private readonly GeneratorExecutorSetupParameters $setupParameters,
		private readonly int $contextId
	){}

	public function onRun() : void{
		$setupParameters = $this->setupParameters;
		$generator = $setupParameters->createGenerator();
		ThreadLocalGeneratorContext::register(new ThreadLocalGeneratorContext($generator, $setupParameters->worldMinY, $setupParameters->worldMaxY), $this->contextId);
	}
}
