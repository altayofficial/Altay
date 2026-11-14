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

use PHPUnit\Framework\TestCase;
use pocketmine\form\element\Button;
use pocketmine\form\element\Divider;
use pocketmine\form\element\Dropdown;
use pocketmine\form\element\Header;
use pocketmine\form\element\Input;
use pocketmine\form\element\Label;
use pocketmine\form\element\Slider;
use pocketmine\form\element\StepSlider;
use pocketmine\form\element\Toggle;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class FormTest extends TestCase{

	private static function encode(TranslatableForm $form) : string{
		return json_encode($form->serializeForLanguage(new Language(Language::FALLBACK_LANGUAGE)), JSON_THROW_ON_ERROR);
	}

	private static function simpleForm() : SimpleForm{
		return new SimpleForm(
			"Title",
			"Content",
			[
				new Header("Header"),
				new Button("Plain"),
				new Divider(),
				new Label("Label"),
				new Button("Icon", FormIcon::path("textures/items/apple")),
			],
			function(Player $player, int $selected) : void{}
		);
	}

	public function testSimpleFormJson() : void{
		self::assertSame(
			'{"type":"form","title":"Title","content":"Content","elements":[' .
			'{"type":"header","text":"Header"},' .
			'{"type":"button","text":"Plain"},' .
			'{"type":"divider","text":""},' .
			'{"type":"label","text":"Label"},' .
			'{"type":"button","text":"Icon","image":{"type":"path","data":"textures\/items\/apple"}}' .
			']}',
			self::encode(self::simpleForm())
		);
	}

	public function testSimpleFormResponseCountsButtonsOnly() : void{
		$form = self::simpleForm();
		self::assertSame(1, $form->parseResponse(1));
		self::assertSame("Icon", $form->getButtons()[1]->getText());
	}

	public function testSimpleFormRejectsBadIndex() : void{
		$this->expectException(FormValidationException::class);
		self::simpleForm()->parseResponse(2);
	}

	public function testModalForm() : void{
		$form = new ModalForm("T", "C", "Yes", "No", function(Player $player, bool $confirmed) : void{});
		self::assertSame(
			'{"type":"modal","title":"T","content":"C","button1":"Yes","button2":"No"}',
			self::encode($form)
		);
		self::assertFalse($form->parseResponse(false));

		$this->expectException(FormValidationException::class);
		$form->parseResponse(1);
	}

	public function testTranslatableTexts() : void{
		$form = new ModalForm(new Translatable("language.name"), "C", "Y", "N", function(Player $player, bool $confirmed) : void{});
		self::assertSame(
			'{"type":"modal","title":"English","content":"C","button1":"Y","button2":"N"}',
			self::encode($form)
		);
	}

	private static function customForm() : CustomForm{
		return new CustomForm(
			"Settings",
			[
				new Header("Header"),
				new Toggle("pvp", "PvP", true, "tip"),
				new Divider(),
				new Slider("fov", "FOV", 30, 110, 5, 70),
				new StepSlider("diff", "Difficulty", ["easy", "hard"], 1),
				new Label("Label"),
				new Dropdown("mode", "Mode", ["a", "b"], tooltip: "pick"),
				new Input("nick", "Nick", "hint", "Steve"),
			],
			function(Player $player, CustomFormResponse $response) : void{},
			submitText: "Save",
			icon: FormIcon::url("https://example.com/i.png")
		);
	}

	public function testCustomFormJson() : void{
		self::assertSame(
			'{"type":"custom_form","title":"Settings","content":[' .
			'{"type":"header","text":"Header"},' .
			'{"type":"toggle","text":"PvP","default":true,"tooltip":"tip"},' .
			'{"type":"divider","text":""},' .
			'{"type":"slider","text":"FOV","min":30,"max":110,"step":5,"default":70},' .
			'{"type":"step_slider","text":"Difficulty","steps":["easy","hard"],"default":1},' .
			'{"type":"label","text":"Label"},' .
			'{"type":"dropdown","text":"Mode","options":["a","b"],"default":0,"tooltip":"pick"},' .
			'{"type":"input","text":"Nick","placeholder":"hint","default":"Steve"}' .
			'],"submit":"Save","icon":{"type":"url","data":"https:\/\/example.com\/i.png"}}',
			self::encode(self::customForm())
		);
	}

	public function testCustomFormResponse() : void{
		$response = self::customForm()->parseResponse([null, false, null, 45, 0, null, 1, "Alex"]);
		self::assertFalse($response->getBool("pvp"));
		self::assertSame(45.0, $response->getFloat("fov"));
		self::assertSame(0, $response->getInt("diff"));
		self::assertSame(1, $response->getInt("mode"));
		self::assertSame("Alex", $response->getString("nick"));
	}

	public function testCustomFormRejectsWrongCount() : void{
		$this->expectException(FormValidationException::class);
		self::customForm()->parseResponse([false, 45, 0, 1, "Alex"]);
	}

	public function testCustomFormRejectsValueForLabel() : void{
		$this->expectException(FormValidationException::class);
		self::customForm()->parseResponse(["x", false, null, 45, 0, null, 1, "Alex"]);
	}

	public function testCustomFormRejectsOutOfRangeSlider() : void{
		$this->expectException(FormValidationException::class);
		self::customForm()->parseResponse([null, false, null, 500, 0, null, 1, "Alex"]);
	}

	public function testDuplicateInputName() : void{
		$this->expectException(\InvalidArgumentException::class);
		new CustomForm("T", [new Toggle("a", "A"), new Input("a", "B")], function(Player $player, CustomFormResponse $response) : void{});
	}

	public function testBadCallbackSignature() : void{
		$this->expectException(\TypeError::class);
		new ModalForm("T", "C", "Y", "N", function(Player $player, string $wrong) : void{}); // @phpstan-ignore argument.type
	}
}
