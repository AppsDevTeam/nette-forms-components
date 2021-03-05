<?php

namespace ADT\Forms;

use ADT\EmailStrictInput;
use ADT\Forms\Controls\PhoneNumberInput;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Vodacek\Forms\Controls\DateInput;
use Closure;

/**
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
 * @method StaticContainer addStaticContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method DynamicContainer addDynamicContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 */
class Form extends \Nette\Application\UI\Form
{
	const RENDERER_BOOTSTRAP4 = 'bootstrap4';
	const RENDERER_BOOTSTRAP5 = 'bootstrap5';

	public static string $renderer = self::RENDERER_BOOTSTRAP5;

	public function __construct(Nette\ComponentModel\IContainer $parent = null, string $name = null)
	{
		parent::__construct($parent, $name);

		$this->onRender[] = [self::class, self::$renderer];
	}

	/**
	 * Adds global error message.
	 * @param  string|object  $message
	 */
	public function addError($message, bool $translate = true): void
	{
		if ($translate && $this->getTranslator()) {
			$message = $this->getTranslator()->translate($message);
		}
		parent::addError($message, false);
	}

	public static function bootstrap4(Nette\Forms\Form $form): void
	{
		$renderer = $form->getRenderer();
		$renderer->wrappers['error']['container'] = 'div';
		$renderer->wrappers['error']['item'] = 'div class="alert alert-danger"';
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['group']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['control']['.error'] = 'is-invalid';
		$renderer->wrappers['control']['.file'] = 'form-control-file';
		$renderer->wrappers['control']['errorcontainer'] = 'div class=invalid-feedback';
		$renderer->wrappers['control']['erroritem'] = 'div';
		$renderer->wrappers['control']['description'] = 'small class=form-text text-muted';

		// we need to create a template container for ToManyContainer
		// to apply bootstrap4 styles below
		/** @var ToManyContainer $_toManyContainer */
		foreach ($form->getComponents(true, ToManyContainer::class) as $_toManyContainer) {
			if ($_toManyContainer->isAllowAdding()) {
				$_toManyContainer->getTemplate();
			}
		}

		/** @var BaseControl $control */
		foreach ($form->getControls() as $control) {
			$type = $control->getOption('type');
			if ($type === 'button') {
				if ($control->getValidationScope() !== null) {
					$control->getControlPrototype()->addClass('btn btn-outline-secondary');
				} else {
					$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-outline-secondary');
					$usedPrimary = true;
				}

			} elseif (in_array($type, ['checkbox', 'radio'], true)) {
				if ($control instanceof Checkbox) {
					$control->getLabelPrototype()->addClass('form-check-label');
				} else {
					$control->getItemLabelPrototype()->addClass('form-check-label');
				}
				$control->getControlPrototype()->addClass('form-check-input');
				$control->getSeparatorPrototype()->setName('div')->addClass('form-check');

			} elseif ($control instanceof PhoneNumberInput) {
				$control->getControlPrototype(PhoneNumberInput::CONTROL_COUNTRY_CODE)->addClass('form-control');
				$control->getControlPrototype(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->addClass('form-control');

			} else {
				$control->getControlPrototype()->addClass('form-control');
			}
		}
	}

	public static function bootstrap5(Nette\Forms\Form $form): void
	{
		static::bootstrap4($form);

		$renderer = $form->getRenderer();
		$renderer->wrappers['pair']['container'] = 'div class=mb-3';
		$renderer->wrappers['control']['.file'] = 'form-control';

		foreach ($form->getControls() as $control) {
			$control->getLabelPrototype()->addClass('form-label');
		}
	}
}
