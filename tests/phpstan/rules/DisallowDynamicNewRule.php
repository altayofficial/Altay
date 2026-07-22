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

namespace pocketmine\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @phpstan-implements Rule<New_>
 */
final class DisallowDynamicNewRule implements Rule{

	public function getNodeType() : string{
		return New_::class;
	}

	public function processNode(Node $node, Scope $scope) : array{
		/** @var New_ $node */
		if($node->class instanceof Expr){
			return [
				RuleErrorBuilder::message("Dynamic new is not allowed.")
					->tip("For factories, use closures instead. Closures can implement custom logic, are statically analyzable, and don't restrict the constructor signature.")
					->identifier("pocketmine.new.dynamic")
					->build()
			];
		}

		return [];
	}
}
