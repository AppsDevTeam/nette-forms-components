<?php

namespace ADT\Forms;

use ADT\Forms\Controls\CurrencyInput;
use ADT\Forms\Controls\PhoneNumberInput;
use ADT\Forms\DynamicContainer;
use ADT\Forms\StaticContainer;
use Vodacek\Forms\Controls\DateInput;
use ADT\EmailStrictInput;
use \Closure;

/**
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
 * @method StaticContainer addStaticContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method DynamicContainer addDynamicContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 */
trait AnnotationsTrait
{

}
