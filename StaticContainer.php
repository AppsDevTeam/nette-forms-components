<?php

namespace ADT\Forms\Controls;

use Closure;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\BaseControl;

class StaticContainer extends BaseContainer
{
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
		if (
			$this->isRequired()
			&&
			$this->isEmpty()
		) {
			$this->addError($this->getRequiredMessage());
		}
	}


	public function getIsFilledComponent(): BaseControl
	{
		return $this->isFilledComponent;
	}


	public function setIsFilledComponent(BaseControl $isFilledComponent): self
	{
		$this->isFilledComponent = $isFilledComponent;
		return $this;
	}


	public function isEmpty($excludeIsFilledComponent = false): bool
	{
		// we don't want to validate the controls, just check if they are empty or not
		// getValues causes a loop
		$values = $this->getUnsafeValues('array');
		if ($excludeIsFilledComponent) {
			unset($values[$this->getIsFilledComponent()->getName()]);
		}
		foreach ($values as &$_value) {
			if ($_value instanceof Nette\Http\FileUpload && !$_value->isOk()) {
				$_value = null;
			}
		}

		return !array_filter($values);
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
