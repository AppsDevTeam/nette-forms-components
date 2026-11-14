<?php

declare(strict_types=1);

// The extension methods are there after autoload alone, without calling register()

use Nette\Forms\Container;
use Nette\Forms\Form;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$form = new Form;
Assert::type(Container::class, $form->addStaticContainer('static', fn(Container $container) => $container->addText('name')));
Assert::type(Container::class, $form->addDynamicContainer('dynamic', fn(Container $container) => $container->addText('name')));
Assert::type(ADT\Forms\Controls\PasswordRevealInput::class, $form->addPasswordReveal('password', false));
