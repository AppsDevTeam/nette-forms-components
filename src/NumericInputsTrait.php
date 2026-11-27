<?php

declare(strict_types=1);

namespace ADT\Forms;

use ADT\Forms\Controls\NumericInput;
use Nette\Forms\Form as NetteForm;
use Stringable;

/**
 * Numbers in a text input instead of `<input type="number">`.
 *
 * The browser changes the value of a number input by the mouse wheel while the input has focus
 * and the pointer is over it - scrolling the page silently rewrites the number. Arrow keys and
 * the spin buttons do the same, and the spin buttons cover the validation icon.
 *
 * The validation is the same as in Nette (Integer / Float rule on the client and on the server,
 * Float accepts the decimal comma); only the HTML type differs. A Min / Max / Range rule does not
 * add `min` / `max` attributes to a text input - it is validated by Nette only. The numeric
 * keyboard of touch devices see {@see NumericInput}.
 *
 * Who needs a real number input can still call `setHtmlType('number')`.
 */
trait NumericInputsTrait
{
	public function addInteger(string $name, string|Stringable|null $label = null): NumericInput
	{
		return $this[$name] = (new NumericInput($label, NumericInput::INPUT_MODE_INTEGER))
			->addRule(NetteForm::Integer);
	}

	public function addFloat(string $name, string|Stringable|null $label = null): NumericInput
	{
		return $this[$name] = (new NumericInput($label, NumericInput::INPUT_MODE_DECIMAL))
			->addRule(NetteForm::Float);
	}
}
