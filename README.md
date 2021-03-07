# Nette Forms Components

The package contains `ADT\Forms\Form` class, which:

- is `Nette\Application\UI\Form` subclass
- using `ADT\Forms\BoostrapFormRenderer` to render form in Bootstrap 5
- overrides `addError` method (the parent method does not use the translator lazy)

You can also use `ADT\Forms\BoostrapFormRenderer` on its own (for example in `UblabooDatagrid::createComponentFilter` method, which creates `Nette\Application\UI\Form` instance).

You can switch to Bootstrap 4 calling `ADT\Forms\BoostrapFormRenderer::$version = ADT\Forms\BootstrapFormRender::VERSION_4;` (for example in your `BasePresenter::beforeRender` method).

Under the hook, if the request is AJAX and the form is not valid, `ADT\Forms\BoostrapFormRenderer` send payload with error messages. 

## Components

All components can be used on their own (without using `ADT\Forms\Form`).

All components can be registered in your Bootstrap file like `ComponentName::register();` (for example `StaticContainer::register();`). This will allow you to use `addComponentName` methods (for example `$form->addStaticContainer('name')`) in your forms. All extensions are added via @method annotation to the appropriate places so IDE auto completion should work out of the box.

### List

- StaticContainer
- DynamicContainer
