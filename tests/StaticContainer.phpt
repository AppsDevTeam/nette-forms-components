<?php

/**
 * @testCase
 */

declare(strict_types=1);

namespace Tests;

use ADT\Forms\StaticContainer;
use Nette\Forms\Form;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

final class StaticContainerTest extends TestCase
{
	public function testWithoutValuesIsEmpty(): void
	{
		Assert::true($this->createContainer()->isEmpty());
	}

	public function testFilledControlIsNotEmpty(): void
	{
		$container = $this->createContainer();
		$container['email']->setDefaultValue('novak@example.com');

		Assert::false($container->isEmpty());
	}

	/**
	 * Nette omits a disabled control from the values, but it keeps its value and the user
	 * could not have emptied it - a container with only locked, filled controls is not empty.
	 */
	public function testDisabledFilledControlIsNotEmpty(): void
	{
		$container = $this->createContainer();
		$container['email']->setDisabled()->setDefaultValue('novak@example.com');

		Assert::false($container->isEmpty());
	}

	public function testDisabledEmptyControlIsEmpty(): void
	{
		$container = $this->createContainer();
		$container['email']->setDisabled();

		Assert::true($container->isEmpty());
	}

	public function testDisabledSubmitButtonIsIgnored(): void
	{
		$container = $this->createContainer();
		$container['search']->setDisabled();

		Assert::true($container->isEmpty());
	}

	private function createContainer(): StaticContainer
	{
		$form = new Form;
		// The factory runs on attaching to a presenter, the controls are added directly.
		$container = new StaticContainer('identity', fn() => null, null);
		$form['identity'] = $container;
		$container->addEmail('email');
		$container->addText('firstName');
		$container->addSubmit('search');

		return $container;
	}
}

(new StaticContainerTest)->run();
