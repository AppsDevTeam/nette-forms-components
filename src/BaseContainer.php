<?php

namespace ADT\Forms;

use Closure;
use Nette\Forms\Container;

abstract class BaseContainer extends Container
{
	use AnnotationsTrait;

	// because there is no "addError" method in Container class
	// we have to create an IControl instance and call "addError" on it
	// the control must not be an instance of "HiddenField"
	// otherwise the error will be added to the form instead of the container
	const string ERROR_CONTROL_NAME = '_containerError_';


	private array $options = [];
	private ?string $requiredMessage = null;


	/**
	 * @param string|null $message
	 * @return static
	 */
	public function setRequired(?string $message): static
	{
		$this->requiredMessage = $message;
		return $this;
	}


	protected function getRequiredMessage(): ?string
	{
		return $this->requiredMessage;
	}


	protected function isRequired(): bool
	{
		return (bool) $this->getRequiredMessage();
	}


	public function setOption($key, $value): static
	{
		if ($value === null) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}


	public function getOption($key, $default = null)
	{
		return $this->options[$key] ?? $default;
	}


	public function getOptions(): array
	{
		return $this->options;
	}


	public function addError($message, bool $translate = true): void
	{
		$this->addText(static::ERROR_CONTROL_NAME)
			->addError($message, $translate);
	}


	public static function register(): void
	{
		Container::extensionMethod('addStaticContainer', function (Container $_this, string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			$control = (new StaticContainerFactory($name, $factory, $isFilledComponentName))
				->create()
				->setRequired($isRequiredMessage);
			$control->currentGroup = $_this->currentGroup;
			$_this->currentGroup?->add($control);
			return $_this[$name] = $control;
		});

		Container::extensionMethod('addDynamicContainer', function (Container $_this, string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			$control = (new DynamicContainer)
				->setStaticContainerFactory(new StaticContainerFactory($name, $factory, $isFilledComponentName))
				->setRequired($isRequiredMessage);
			$control->currentGroup = $_this->currentGroup;
			$_this->currentGroup?->add($control);
			return $_this[$name] = $control;
		});
	}
}
