<?php

namespace ADT\Forms;

use Closure;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Http\FileUpload;

class StaticContainer extends BaseContainer
{
	use GetComponentTrait;
	use SectionTrait;
	use ElementsTrait;

	private ?BaseControl $isFilledComponent = null;
	private bool $isTemplate = false;


	public function __construct(string $entityFieldName, Closure $factory, ?string $isFilledComponentName)
	{
		$this->monitor(Presenter::class, function() use ($entityFieldName, $factory, $isFilledComponentName) {
			$factory($this);

			if ($isFilledComponentName) {
				$this->setIsFilledComponent($this->addText($isFilledComponentName)->setHtmlAttribute('style', 'display: none'));
			}
		});
	}


	public function validate(?array $controls = NULL): void
	{
		parent::validate($controls);

		if (
			$this->isRequired()
			&&
			$this->isEmpty()
		) {
			$this->addError($this->getRequiredMessage());
		}
	}


	public function getIsFilledComponent(): ?BaseControl
	{
		return $this->isFilledComponent;
	}


	public function setIsFilledComponent(BaseControl $isFilledComponent): self
	{
		$this->isFilledComponent = $isFilledComponent;
		return $this;
	}


	public function isEmpty(bool $excludeIsFilledComponent = false): bool
	{
		// we don't want to validate the controls, just check if they are empty, or not
		// getValues causes a loop
		$values = $this->getUntrustedValues('array');
		if ($excludeIsFilledComponent) {
			unset($values[$this->getIsFilledComponent()->getName()]);
		}
		foreach ($values as &$_value) {
			if ($_value instanceof FileUpload && !$_value->isOk()) {
				$_value = null;
			}
		}
		if (array_filter($values)) {
			return false;
		}

		// A disabled control is omitted from the values, but the user could not have emptied it:
		// a container whose only filled controls are disabled (locked read-only fields) is not
		// empty - otherwise the entity mapper would delete the related entity on every save.
		foreach ($this->getControls() as $_control) {
			if ($_control instanceof BaseControl && !$_control instanceof Button && $_control->isDisabled() && $_control->getValue()) {
				return false;
			}
		}

		return true;
	}


	public function isTemplate(): bool
	{
		return $this->isTemplate;
	}


	public function setIsTemplate(bool $isTemplate): self
	{
		$this->isTemplate = $isTemplate;
		return $this;
	}
}
