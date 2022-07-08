<?php

namespace ADT\Forms;

use Nette;
use Nette\ComponentModel\IComponent;

trait GetComponentTrait
{
	abstract public function getComponent(string $name, bool $throw = true): ?IComponent;
	
	public function getComponentButton(string $name, bool $throw = true): ?Nette\Forms\Controls\Button
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDate(string $name, bool $throw = true): ?\Vodacek\Forms\Controls\DateInput
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentPhoneNumber(string $name, bool $throw = true): ?\ADT\Forms\Controls\PhoneNumberInput
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentCurrency(string $name, bool $throw = true): ?\ADT\Forms\Controls\CurrencyInput
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentStaticContainer(string $name, bool $throw = true): ?StaticContainer
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDynamicContainer(string $name, bool $throw = true): ?DynamicContainer
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentUploadControl(string $name, bool $throw = true): ?Nette\Forms\Controls\UploadControl
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDependentSelectBox(string $name, bool $throw = true): ?\NasExt\Forms\Controls\DependentSelectBox
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDependentMultiSelectBox(string $name, bool $throw = true): ?\NasExt\Forms\Controls\DependentMultiSelectBox
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentAjaxSelect(string $name, bool $throw = true): ?\ADT\Components\AjaxSelect\AjaxSelect
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentAjaxMultiSelect(string $name, bool $throw = true): ?\ADT\Components\AjaxSelect\AjaxMultiSelect
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDynamicSelect(string $name, bool $throw = true): ?\ADT\Components\AjaxSelect\DynamicSelect
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentDynamicMultiSelect(string $name, bool $throw = true): ?\ADT\Components\AjaxSelect\DynamicMultiSelect
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentCheckbox(string $name, bool $throw = true): ?Nette\Forms\Controls\Checkbox
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentCheckboxList(string $name, bool $throw = true): ?Nette\Forms\Controls\CheckboxList
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentMultiSelectBox(string $name, bool $throw = true): ?Nette\Forms\Controls\MultiSelectBox
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentRadioList(string $name, bool $throw = true): ?Nette\Forms\Controls\RadioList
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentSelectBox(string $name, bool $throw = true): ?Nette\Forms\Controls\SelectBox
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentSubmitButton(string $name, bool $throw = true): ?Nette\Forms\Controls\SubmitButton
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentTextArea(string $name, bool $throw = true): ?Nette\Forms\Controls\TextArea
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentTextInput(string $name, bool $throw = true): ?Nette\Forms\Controls\TextInput
	{
		return parent::getComponent($name, $throw);
	}

	public function getComponentHiddenField(string $name, bool $throw = true): ?Nette\Forms\Controls\HiddenField
	{
		return parent::getComponent($name, $throw);
	}
}