<?php

declare(strict_types=1);

namespace ADT\Forms\Controls;

use Nette\Forms\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Rules;
use Nette\Utils\Html;
use Stringable;

/**
 * Number in a text input instead of `<input type="number">` - see {@see \ADT\Forms\NumericInputsTrait}.
 *
 * The numeric keyboard of touch devices (`inputmode`) is used only when the value cannot be negative:
 * iOS has no numeric keyboard with a minus key, a field that allows negative numbers keeps the
 * standard keyboard (numbers are behind its "123" key). It is decided when the input is rendered,
 * so it covers the rules added after the control was created. An explicit `inputmode` attribute
 * wins.
 */
class NumericInput extends TextInput
{
	public const string INPUT_MODE_INTEGER = 'numeric';
	public const string INPUT_MODE_DECIMAL = 'decimal';

	public function __construct(string|Stringable|null $label, private readonly string $inputMode)
	{
		parent::__construct($label);

		$this->setNullable();
		// before the Integer rule, otherwise Nette switches the type to `number`
		$this->setHtmlType('text');
	}

	public function getControl(): Html
	{
		$input = parent::getControl();

		if ($input->inputmode === null && $this->hasNonNegativeMinimum($this->getRules())) {
			$input->inputmode = $this->inputMode;
		}

		return $input;
	}

	/**
	 * Min or Range rule of this control with a lower bound >= 0, also inside a Filled condition
	 * (`addCondition(Form::Filled)->addRule(Form::Min, ...)`). Other conditions do not apply always.
	 */
	private function hasNonNegativeMinimum(Rules $rules): bool
	{
		foreach ($rules as $rule) {
			if ($rule->isNegative || $rule->control !== $this) {
				continue;
			}

			if ($rule->branch) {
				if ($rule->validator === Form::Filled && $this->hasNonNegativeMinimum($rule->branch)) {
					return true;
				}

				continue;
			}

			$minimum = match ($rule->validator) {
				Form::Min => $rule->arg,
				Form::Range => is_array($rule->arg) ? ($rule->arg[0] ?? null) : null,
				default => null,
			};

			if (is_numeric($minimum) && $minimum >= 0) {
				return true;
			}
		}

		return false;
	}
}
