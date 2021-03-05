<?php

namespace ADT\Forms;

use Closure;
use ADT\EmailStrictInput;
use ADT\Forms\Controls\DynamicContainer;
use ADT\Forms\Controls\PhoneNumberInput;
use ADT\Forms\Controls\StaticContainer;
use Vodacek\Forms\Controls\DateInput;

/**
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
 * @method StaticContainer addStaticContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method DynamicContainer addDynamicContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 */
interface Container
{

}