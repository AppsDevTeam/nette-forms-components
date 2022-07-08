<?php

namespace ADT\Forms;

use ADT\Components\AjaxSelect\AjaxMultiSelect;
use ADT\Components\AjaxSelect\AjaxSelect;
use ADT\Components\AjaxSelect\DynamicSelect;
use ADT\Forms\Controls\CurrencyInput;
use ADT\Forms\Controls\PhoneNumberInput;
use ADT\Forms\DynamicContainer;
use ADT\Forms\StaticContainer;
use NasExt\Forms\Controls\DependentMultiSelectBox;
use NasExt\Forms\Controls\DependentSelectBox;
use Vodacek\Forms\Controls\DateInput;
use ADT\EmailStrictInput;
use \Closure;

/**
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null, $language = null)
 * @method StaticContainer addStaticContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method DynamicContainer addDynamicContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method AjaxSelect addAjaxSelect($name, $label = null, $entityName = null, $entitySetupCallback = null, $config = [])
 * @method AjaxMultiSelect addAjaxMultiSelect($name, $label = null, $entityName = null, $entitySetupCallback = null, $config = [])
 * @method DependentSelectBox addDependentSelectBox($name, $label, \Nette\Forms\Control ...$parents)
 * @method DependentMultiSelectBox addDependentMultiSelectBox($name, $label, \Nette\Forms\Control ...$parents)
 * @method DynamicSelect addDynamicSelect($name, $label = NULL, $items = NULL, $itemFactory = NULL, $config = [])
 */
trait AnnotationsTrait
{

}
