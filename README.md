# Nette Forms Components

## `ADT\Forms\Form`

`Nette\Application\UI\Form` subclass using `ADT\Forms\BoostrapFormRenderer` to render a form in Bootstrap 5.

Overrides `addError` method (the parent method does not use the translator lazy).

## `ADT\Forms\BoostrapFormRenderer`

Can be used on its own, without using `ADT\Forms\Form` (for example in `UblabooDatagrid::createComponentFilter` method, which creates `Nette\Application\UI\Form` instance)

Can be switch to Bootstrap 4 calling `ADT\Forms\BoostrapFormRenderer::$version = ADT\Forms\BootstrapFormRender::VERSION_4;` (for example in your `BasePresenter::beforeRender` method).

If it's an AJAX request and the form is not valid, only snippets with error messages will be sent back to browser (without rendering the form).

## Components

Can be used on their own, without using `ADT\Forms\Form`.

Can be registered in your Bootstrap file like `ComponentName::register();` (for example `StaticContainer::register();`). This will allow you to use `addComponentName` methods (for example `$form->addStaticContainer('name')`) in your forms. All extensions are added via @method annotation to the appropriate places so IDE auto completion should work out of the box.

### List

- StaticContainer
- DynamicContainer
