# Nette Forms Components

The package contains `ADT\Forms\Form` class, which:

- is `Nette\Application\UI\Form` subclass
- has Bootstrap 4 and 5 (default) renderer. You can switch to Bootstrap 4 calling `ADT\Forms\BoostrapFormRenderer::$version = ADT\Forms\BootstrapFormRender::VERSION_4;` (for example in your `BasePresenter::beforeRender` method).
- overrides `addError` method (the parent method does not use the translator lazy)
- under the hook, if the request is AJAX and the form is not valid, returns only error messages in snippets 

You can also use `ADT\Forms\BoostrapFormRenderer` on its own (for example in `Datagrid::createComponentFilter` method).

## Components

All components can be registered in your Bootstrap file like `ComponentName::register();` (for example `StaticContainer::register();`). This will allow you to use `addComponentName` methods (for example `$form->addStaticContainer('name')`) in your forms. All extensions are added via @method annotation so auto completion will work out of the box.

- StaticContainer
- DynamicContainer
