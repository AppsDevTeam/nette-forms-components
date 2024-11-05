<?php

namespace ADT\Forms;

use Exception;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Utils\ArrayHash;
use Nette\Utils\Callback;
use Nette\Utils\Reflection;
use Nette\Utils\Type;
use ReflectionException;

/**
 * @property-read Form $form
 * @method onBeforeInitForm($form)
 * @method onAfterInitForm($form)
 * @method onBeforeValidateForm($form)
 * @method onBeforeProcessForm($form)
 * @method onSuccess($form)
 */
abstract class BaseForm extends Control
{
	/** @var Form */
	protected $form;

	/** @var string|null */
	public ?string $templateFilename = null;

	/** @var bool */
	public bool $isAjax = true;

	/** @var bool */
	public bool $emptyHiddenToggleControls = false;

	/** @var callable[] */
	protected array $paramResolvers = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onBeforeInitForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onAfterInitForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onBeforeValidateForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onBeforeProcessForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onSuccess = [];

	public function __construct()
	{
		$this->paramResolvers[] = function(string $type, $values = null) {
			if ($type === Form::class || is_subclass_of($type, Form::class)) {
				return $this->form;
			} elseif ($type === Presenter::class || is_subclass_of($type, Presenter::class)) {
				return $this->presenter;
			} elseif ($values) {
				if ($type === ArrayHash::class) {
					return $values;
				} elseif ($type === 'array') {
					return (array) $values;
				}
			}

			return false;
		};

		$this->monitor(Presenter::class, function() {
			$form = $this->form = $this['form'] = $this->createComponentForm();

			/** @link BaseForm::validateFormCallback() */
			$form->onValidate[] = [$this, 'validateFormCallback'];

			/** @link BaseForm::processFormCallback() */
			$form->onSuccess[] = [$this, 'processFormCallback'];

			foreach ($this->onBeforeInitForm as $_callback) {
				$_callback($form);
			}
			$this->onBeforeInitForm($form);

			if (!method_exists($this, 'initForm')) {
				throw new Exception('Please define the "initForm($form)" method.');
			}
			$this->invokeHandler([$this, 'initForm']);

			foreach ($this->onAfterInitForm as $_callback) {
				$_callback($form);
			}
			$this->onAfterInitForm($form);

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

	/**
	 * @throws ReflectionException
	 */
	final public function validateFormCallback(Form $form): void
	{
		$this->onBeforeValidateForm($form);

		if ($form->isValid() && method_exists($this, 'validateForm')) {
			$this->invokeHandler([$this, 'validateForm'], $form->getUnsafeValues(null));
		}
	}

	/**
	 * @throws Exception
	 */
	final public function processFormCallback(Form $form): void
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
						$_control->setOption('hidden', true);
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

	/**
	 * @throws ReflectionException
	 */
	public function render(): void
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
			$this->form->getElementPrototype()->class[] = 'ajax';
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl('formArea');
		}

		if (method_exists($this, 'renderForm')) {
			$this->invokeHandler([$this, 'renderForm']);
		}

		$this->template->render();
	}

	protected function createComponentForm(): Form
	{
		return new Form();
	}

	protected function _()
	{
		return call_user_func_array([$this->form->getTranslator(), 'translate'], func_get_args());
	}

	public function setOnBeforeInitForm(callable $onBeforeInitForm)
	{
		$this->onBeforeInitForm[] = $onBeforeInitForm;
		return $this;
	}

	public function setOnAfterInitForm(callable $onAfterInitForm)
	{
		$this->onAfterInitForm[] = $onAfterInitForm;
		return $this;
	}

	public function setOnBeforeValidateForm(callable $onBeforeValidateForm)
	{
		$this->onBeforeValidateForm[] = $onBeforeValidateForm;
		return $this;
	}

	public function setOnBeforeProcessForm(callable $onBeforeProcessForm)
	{
		$this->onBeforeProcessForm[] = $onBeforeProcessForm;
		return $this;
	}

	public function setOnSuccess(callable $onSuccess)
	{
		$this->onSuccess[] = $onSuccess;
		return $this;
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function invokeHandler($handler, $formValues = null)
	{
		$types = array_map(function(\ReflectionParameter $param) {
			return Type::resolve($param->getType()->getName(), $param);
		}, Callback::toReflection($handler)->getParameters());

		$params = [];
		foreach ($types as $_type) {
			if (empty($_type)) {
				throw new Exception('All parameter types must be specified.');
			}

			$param = null;
			foreach ($this->paramResolvers as $_paramResolver) {
				if (($param = $_paramResolver($_type, $formValues)) !== false) {
					$params[] = $param;
					break;
				}
			}

			if ($param === false) {
				throw new Exception('No resolver found for type ' . $_type . '.');
			}
		}

		$handler(...$params);
	}

	/**
	 * @deprecated Use $this->form instead
	 * @return Form
	 */
	public function getForm()
	{
		return $this['form'];
	}
}
