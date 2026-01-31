<?php

namespace ADT\Forms;

use Nette;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\BaseControl;
use stdClass;

class Form extends Nette\Application\UI\Form
{
	use AnnotationsTrait;
	use GetComponentTrait;
	use SectionTrait;
	use ElementsTrait;

	const string GROUP_LEVEL_SEPARATOR = '-';

	private ?BootstrapFormRenderer $renderer = null;
	/** @var Nette\Forms\ControlGroup[] */
	public array $ancestorGroups = [];

	public function __construct(?Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);

		$this->monitor(Presenter::class, function() {
			// must be called here because onError and onRender callbacks are set in the constructor
			$this->getRenderer();
		});
	}

	public function getRenderer(): BootstrapFormRenderer
	{
		if ($this->renderer === null) {
			$this->renderer = new BootstrapFormRenderer($this);
		}
		return $this->renderer;
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

	public function watchForSubmit(BaseControl $control): void
	{
		$control->setHtmlAttribute('data-adt-redraw-snippet', $this->addSubmit('submit')->getHtmlName());
	}

	/**
	 * Returns only validated values from the form.
	 * Values from controls that have validation errors are excluded.
	 */
	public function getValidatedValues(string|object|bool|null $returnType = null): object|array
	{
		$allValues = $this->getUntrustedValues($returnType);
		return $this->filterValidValues($this, $allValues);
	}

	private function filterValidValues(Nette\Forms\Container $container, $values): array|stdClass
	{
		$result = is_array($values) ? [] : new stdClass;
		$isArray = is_array($values);

		foreach ($container->getComponents() as $name => $component) {
			$name = (string) $name;

			// Zkontroluj, jestli hodnota existuje
			if ($isArray) {
				if (!array_key_exists($name, $values)) {
					continue;
				}
				$value = $values[$name];
			} else {
				if (!property_exists($values, $name)) {
					continue;
				}
				$value = $values->$name;
			}

			if ($component instanceof Nette\Forms\Container) {
				// Rekurzivně filtruj vnořený kontejner
				$filtered = $this->filterValidValues($component, $value);
				if (is_array($result)) {
					$result[$name] = $filtered;
				} else {
					$result->$name = $filtered;
				}
			} elseif ($component instanceof BaseControl) {
				// Zkontroluj, jestli je control validní
				if ($this->isControlValid($component)) {
					if (is_array($result)) {
						$result[$name] = $value;
					} else {
						$result->$name = $value;
					}
				}
			}
		}

		return $result;
	}

	private function isControlValid(BaseControl $control): bool
	{
		if ($control->isDisabled()) {
			return false;
		}

		$rules = $control->getRules();
		$emptyOptional = !$rules->isRequired() && !$control->isFilled();

		return $this->validateBranch($rules, $emptyOptional);
	}

	private function validateBranch(Nette\Forms\Rules $branch, bool $emptyOptional): bool
	{
		foreach ($branch as $rule) {
			if (!$rule->branch && $emptyOptional && $rule->validator !== Nette\Forms\Form::Filled) {
				continue;
			}

			$success = $branch::validateRule($rule);
			if (!$success && !$rule->branch) {
				return false;
			}

			if ($success && $rule->branch) {
				if (!$this->validateBranch($rule->branch, $rule->validator === Nette\Forms\Form::Blank ? false : $emptyOptional)) {
					return false;
				}
			}
		}

		return true;
	}
}
