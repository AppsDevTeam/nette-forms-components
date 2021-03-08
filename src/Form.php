<?php

namespace ADT\Forms;

use ADT\EmailStrictInput;
use ADT\Forms\Controls\PhoneNumberInput;
use Nette;
use Nette\Application\UI\Presenter;
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
	public function __construct(Nette\ComponentModel\IContainer $parent = null, string $name = null)
	{
		parent::__construct($parent, $name);

		$this->monitor(Presenter::class, function($presenter) {
			// must be called here because onError and onRender callbacks are set in the constructor
			$this->getRenderer();
		});
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
}
