<?php

namespace ADT\Forms;

use ADT\Forms\Form;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Forms\IControl;

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

	public function __construct()
	{
		$this->paramResolvers[] = function() {
			if (is_subclass_of($type, Form::class)) {
				return $this->getForm();
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

			if ($form->isSubmitted()) {
				if (is_bool($form->isSubmitted())) {
					$form->setSubmittedBy(null);
				}
				elseif ($form->isSubmitted()->getValidationScope() !== null) {
					$form->onValidate = [];
				}
			}
		});
	}
	
	final public function validateFormCallback($form): void
	{
		$this->onBeforeValidateForm($form);

		if (method_exists($this, 'validateForm')) {
			$this->invokeHandler([$this, 'validateForm'], $form->getUnsafeValues(null));
		}
	}

	final public function processFormCallback($form)
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

		if (method_exists($this, 'processForm')) {
			$this->invokeHandler([$this, 'processForm'], $form->getValues());
		}

		if ($form->isValid()) {
			$this->invokeHandler([$this, 'onSuccess'], $form->getValues());
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

	private function invokeHandler($handler, $defaultParam)
	{
		$types = array_map([\Nette\Utils\Reflection::class, 'getParameterType'], \Nette\Utils\Callback::toReflection($handler)->getParameters());

		$params = [];
		foreach ($types as $_type) {
			if (empty($_type)) {
				$params[] = $defaultParam;
				continue;
			}

			$param = null;
			foreach ($this->paramResolvers as $_paramResolver) {
				if ($param = $_paramResolver($_type)) {
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
}
