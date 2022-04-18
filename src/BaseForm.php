<?php

namespace ADT\Forms;

use Nette\Forms\Controls\TextBase;
use ADT\Forms\Form;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;

/**
 * @property-read Form $form
 * @method onBeforeInitForm($form)
 * @method onAfterInitForm($form)
 * @method onBeforeValidateForm($form)
 * @method onBeforeProcessForm($form)
 * @method onSucess($form)
 */
abstract class BaseForm extends Control
{
	const OPTION_ALLOW_4_BYTE_CHARACTERS = 'allow4ByteCharacters';

	/** @var string|null */
	public ?string $templateFilename = null;

	/** @var bool */
	public bool $isAjax = true;

	/** @var bool */
	public bool $emptyHiddenToggleControls = false;

	/** @var callable[] */
	protected $paramResolvers = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onBeforeInitForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onAfterInitForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onBeforeValidateForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onBeforeProcessForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onSuccess = [];

	protected ?string $disallow4ByteCharacterMessage = null;

	public function __construct()
	{
		$this->paramResolvers[] = function(string $type, $values) {
			if ($type === Form::class || is_subclass_of($type, Form::class)) {
				return $this->getForm();
			} elseif ($type === ArrayHash::class) {
				return $values;
			} elseif ($type === 'array') {
				return (array) $values;
			}

			return false;
		};

		$this->monitor(Presenter::class, function($presenter) {
			$form = $this->getForm();

			/** @link BaseForm::validateFormCallback() */
			$form->onValidate[] = [$this, 'validateFormCallback'];

			/** @link BaseForm::processFormCallback() */
			$form->onSuccess[] = [$this, 'processFormCallback'];

			$this->onBeforeInitForm($form);

			$this->initForm($form);

			$this->onAfterInitForm($form);

			$this->add4ByteCharacterRule($form);

			if ($form->isSubmitted()) {
				if (is_bool($form->isSubmitted()) || $form->isSubmitted()->isDisabled()) {
					$form->setSubmittedBy(null);
				}
				elseif ($form->isSubmitted()->getValidationScope() !== null) {
					$form->onValidate = [];
				}
			}
		});
	}

	final public function validateFormCallback(Form $form): void
	{
		$this->onBeforeValidateForm($form);

		if ($form->isValid() && method_exists($this, 'validateForm')) {
			$this->invokeHandler([$this, 'validateForm'], $form->getUnsafeValues(null));
		}
	}

	final public function processFormCallback(Form $form)
	{
		if ($form->isSubmitted()->getValidationScope() !== null) {
			return;
		}

		// empty hidden toggles
		if ($this->emptyHiddenToggleControls) {
			$toggles = $form->getToggles();
			foreach ($form->getGroups() as $_group) {
				$label = $_group->getOption('label');
				if (isset($toggles[$label]) && $toggles[$label] === false) {
					foreach ($_group->getControls() as $_control) {
						$_control->setValue(null);
					}
				}
			}
		}

		$this->onBeforeProcessForm($form);

		if ($form->isValid()) {
			if (method_exists($this, 'processForm')) {
				$this->invokeHandler([$this, 'processForm'], $form->getValues());
			}

			if ($form->isValid()) {
				foreach ($this->onSuccess as $_handler) {
					$this->invokeHandler($_handler, $form->getValues());
				}
			}
		}
	}

	public function render()
	{
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'form.latte');

		$customTemplatePath = (
		(!empty($this->templateFilename))
			? $this->templateFilename
			: str_replace('.php', '.latte', $this->getReflection()->getFileName())
		);

		if (file_exists($customTemplatePath)) {
			$this->template->customTemplatePath = $customTemplatePath;
		}

		if ($this->isAjax) {
			$this->getForm()->getElementPrototype()->class[] = 'ajax';
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl('formArea');
		}

		$this->template->render();
	}

	protected function createComponentForm()
	{
		return new Form();
	}

	protected function _()
	{
		return call_user_func_array([$this->getForm()->getTranslator(), 'translate'], func_get_args());
	}

	public function setOnBeforeInitForm(callable $onBeforeInitForm): self
	{
		$this->onBeforeInitForm[] = $onBeforeInitForm;
		return $this;
	}

	public function setOnAfterInitForm(callable $onAfterInitForm): self
	{
		$this->onAfterInitForm[] = $onAfterInitForm;
		return $this;
	}

	public function setOnBeforeValidateForm(callable $onBeforeValidateForm): self
	{
		$this->onBeforeValidateForm[] = $onBeforeValidateForm;
		return $this;
	}

	public function setOnBeforeProcessForm(callable $onBeforeProcessForm): self
	{
		$this->onBeforeProcessForm[] = $onBeforeProcessForm;
		return $this;
	}

	public function setOnSuccess(callable $onSuccess): self
	{
		$this->onSuccess[] = $onSuccess;
		return $this;
	}

	/**
	 * @return \ADT\Forms\Form
	 */
	public function getForm()
	{
		return $this['form'];
	}

	private function invokeHandler($handler, $formValues)
	{
		$types = array_map([\Nette\Utils\Reflection::class, 'getParameterType'], \Nette\Utils\Callback::toReflection($handler)->getParameters());

		$params = [];
		foreach ($types as $_type) {
			if (empty($_type)) {
				throw new \Exception('All parameter types must be specified.');
			}

			$param = null;
			foreach ($this->paramResolvers as $_paramResolver) {
				if ($param = $_paramResolver($_type, $formValues)) {
					$params[] = $param;
					break;
				}
			}

			if (!$param) {
				throw new \Exception('No resolver found for type ' . $_type . '.');
			}
		}

		$handler(...$params);
	}
	
	public function disallow4ByteCharacters(string $errorMessage)
	{
		$this->disallow4ByteCharacterMessage = $errorMessage;
	}

	/**
	 * don't allow UTF8 4 bytes characters in TextBase controls
	 * if BaseForm::disallow4ByteCharacterMessage is set
	 * and TextBase::setOption(self::OPTION_ALLOW_4_BYTE_CHARACTERS, true) is not set
	 */
	private function add4ByteCharacterRule(Form $form)
	{
		if (!$this->disallow4ByteCharacterMessage) {
			return;
		}

		foreach ($form->getControls() as $_control) {
			if (!$_control instanceof TextBase) {
				continue;
			}

			if ($_control->getOption(self::OPTION_ALLOW_4_BYTE_CHARACTERS, false)) {
				continue;
			}

			$_control->addRule(function(TextBase $control) {
				return !preg_match_all('~[\x{10000}-\x{10FFFF}]~u', $control->getValue());
			}, $this->disallow4ByteCharacterMessage);
		}
	}
}
