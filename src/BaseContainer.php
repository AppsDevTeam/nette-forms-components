<?php

namespace ADT\Forms;

use ADT\EmailStrictInput;
use ADT\Forms\Controls\PhoneNumberInput;
use Closure;
use Nette\Forms\Container;
use Vodacek\Forms\Controls\DateInput;

/**
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
 * @method StaticContainer addStaticContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 * @method DynamicContainer addDynamicContainer(string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
 */
abstract class BaseContainer extends Container
{
	// because there is no "addError" method in Container class
	// we have to create an IControl instance and call "addError" on it
	// the control must not be an instance of "HiddenField"
	// otherwise the error will be added to the form instead of the container
	const ERROR_CONTROL_NAME = '_containerError_';


	private array $options = [];
	private ?string $requiredMessage = null;


	/**
	 * @param string|null $message
	 * @return static
	 */
	public function setRequired(?string $message)
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


	public function setOption($key, $value)
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
		Container::extensionMethod('addStaticContainer', function (Container $_this, string $name, Closure $Factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			return $_this[$name] = (new StaticContainerFactory($name, $factory, $isFilledComponentName))
				->create()
				->setRequired($isRequiredMessage);
		});

		Container::extensionMethod('addDynamicContainer', function (Container $_this, string $name, Closure $factory, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			return $_this[$name] = (new DynamicContainer)
				->setStaticContainerFactory(new StaticContainerFactory($name, $factory, $isFilledComponentName))
				->setRequired($isRequiredMessage);
		});
	}
}
