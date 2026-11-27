<?php

/**
 * @testCase
 */

declare(strict_types=1);

namespace Tests;

use ADT\Forms\Form;
use ADT\Forms\StaticContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * addInteger / addFloat render a text input (the mouse wheel does not change it) and keep the
 * validation of Nette.
 */
final class NumericInputsTest extends TestCase
{
	public function testIntegerIsTextInput(): void
	{
		$input = $this->createForm()->addInteger('count');

		Assert::same('text', $input->getControl()->type);
	}

	public function testFloatIsTextInput(): void
	{
		$input = $this->createForm()->addFloat('price');

		Assert::same('text', $input->getControl()->type);
		Assert::null($input->getControl()->step);
	}

	/** iOS has no numeric keyboard with a minus key - a field that may be negative keeps the standard one. */
	public function testNumericKeyboardOnlyWithoutNegativeValues(): void
	{
		$form = $this->createForm();

		Assert::null($form->addInteger('any')->getControl()->inputmode);
		Assert::same('numeric', $form->addInteger('count')->addRule(Form::Min, null, 0)->getControl()->inputmode);
		Assert::same('numeric', $form->addInteger('percent')->addRule(Form::Range, null, [1, 100])->getControl()->inputmode);
		Assert::null($form->addInteger('offset')->addRule(Form::Range, null, [-5, 5])->getControl()->inputmode);
		Assert::null($form->addInteger('upTo')->addRule(Form::Range, null, [null, 100])->getControl()->inputmode);
		Assert::null($form->addInteger('max')->addRule(Form::Max, null, 10)->getControl()->inputmode);
		$price = $form->addFloat('price');
		$price->addCondition(Form::Filled)->addRule(Form::Min, null, 0);
		Assert::same('decimal', $price->getControl()->inputmode);
		Assert::null($form->addFloat('balance')->getControl()->inputmode);
	}

	public function testMinimumUnderOtherConditionDoesNotApply(): void
	{
		$form = $this->createForm();
		$switch = $form->addCheckbox('switch');
		$input = $form->addInteger('value');
		$input->addConditionOn($switch, Form::Equal, true)->addRule(Form::Min, null, 0);

		Assert::null($input->getControl()->inputmode);
	}

	public function testExplicitInputModeWins(): void
	{
		$input = $this->createForm()->addInteger('count')->setHtmlAttribute('inputmode', 'text')->addRule(Form::Min, null, 0);

		Assert::same('text', $input->getControl()->inputmode);
	}

	public function testContainerHasTextInputsToo(): void
	{
		$container = new StaticContainer('items', fn() => null, null);
		$this->createForm()['items'] = $container;

		Assert::same('text', $container->addInteger('count')->getControl()->type);
		Assert::same('text', $container->addFloat('price')->getControl()->type);
	}

	public function testIntegerValidation(): void
	{
		Assert::same(12, $this->validate(fn(Container $c) => $c->addInteger('value'), '12'));
		Assert::same(-3, $this->validate(fn(Container $c) => $c->addInteger('value'), '-3'));
		Assert::same(['Please enter a valid integer.'], $this->errors(fn(Container $c) => $c->addInteger('value'), '1.5'));
		Assert::same(['Please enter a valid integer.'], $this->errors(fn(Container $c) => $c->addInteger('value'), 'abc'));
		Assert::null($this->validate(fn(Container $c) => $c->addInteger('value'), ''));
	}

	public function testFloatAcceptsDecimalComma(): void
	{
		Assert::same(1.5, $this->validate(fn(Container $c) => $c->addFloat('value'), '1,5'));
		Assert::same(1.5, $this->validate(fn(Container $c) => $c->addFloat('value'), '1.5'));
		Assert::same(['Please enter a valid number.'], $this->errors(fn(Container $c) => $c->addFloat('value'), 'abc'));
	}

	public function testRangeIsValidatedWithoutMinMaxAttributes(): void
	{
		$factory = fn(Container $c) => $c->addInteger('value')->addRule(Form::Range, null, [1, 100]);

		Assert::same(['Please enter a value between 1 and 100.'], $this->errors($factory, '150'));
		Assert::same(100, $this->validate($factory, '100'));

		$input = $this->createForm()->addInteger('value')->addRule(Form::Range, null, [1, 100]);
		Assert::null($input->getControl()->min);
		Assert::null($input->getControl()->max);
	}

	public function testNumberTypeCanStillBeRequested(): void
	{
		$input = $this->createForm()->addInteger('count')->setHtmlType('number');

		Assert::same('number', $input->getControl()->type);
	}

	private function createForm(): Form
	{
		return new Form;
	}

	/** @param callable(Container): TextInput $factory */
	private function validate(callable $factory, string $value): mixed
	{
		$input = $this->submit($factory, $value);
		Assert::same([], $input->getErrors());

		return $input->getValue();
	}

	/**
	 * @param callable(Container): TextInput $factory
	 * @return list<string>
	 */
	private function errors(callable $factory, string $value): array
	{
		return $this->submit($factory, $value)->getErrors();
	}

	/** @param callable(Container): TextInput $factory */
	private function submit(callable $factory, string $value): TextInput
	{
		$input = $factory($this->createForm());
		$input->setValue($value);
		$input->validate();

		return $input;
	}
}

(new NumericInputsTest)->run();
