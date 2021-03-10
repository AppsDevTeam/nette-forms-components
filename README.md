# Nette Forms Components

## Installation

`composer require adt/nette-forms-components`

## ADT\Forms\Form

`Nette\Application\UI\Form` subclass using `ADT\Forms\BoostrapFormRenderer` to render a form in Bootstrap 5 (default) or 4.

Overrides `addError` method (the parent method does not use the translator lazy).

Have `@method` annotations for all ADT Nette Forms components so PhpStorm auto completion should work out of the box.

## ADT\Forms\BoostrapFormRenderer

Can be used on its own, without using `ADT\Forms\Form` (for example in `Ublaboo\DataGrid\DataGrid::createComponentFilter` method, which creates `Nette\Application\UI\Form` instance)

Can be switch to Bootstrap 4 calling `ADT\Forms\BoostrapFormRenderer::$version = ADT\Forms\BootstrapFormRender::VERSION_4;` (for example in your `BasePresenter::beforeRender` method).

If it's an AJAX request and the form is not valid, only snippets with error messages will be sent back to browser (without rendering the form).

If you need, you can use static methods `ADT\Forms\BoostrapFormRenderer::makeBootstrap` and `ADT\Forms\BoostrapFormRenderer::sendErrorPayload` manually (for example in `\Ublaboo\DataGrid\DataGrid::setItemsDetailForm`, where the container is created dynamically).

You can use `->setOption('description', 'Description text')` to use field description.

You can use `->setOption('prepend', 'Text to prepend')` or `->setOption('append', 'Text to append')` to use proper inpur group styles.

## Containers

Can be used on their own, without using `ADT\Forms\Form`.

Can be registered in your Bootstrap file like `BaseContainer::register();`. This will allow you to use `addStaticContainer` and `addDynamicContainer` methods in your forms.
