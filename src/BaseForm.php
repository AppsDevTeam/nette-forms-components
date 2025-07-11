<?php

namespace ADT\Forms;

use Exception;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Forms\SubmitterControl;
use Nette\Utils\ArrayHash;
use Nette\Utils\Callback;
use Nette\Utils\Type;
use ReflectionException;
use ReflectionParameter;

/**
 * @method onBeforeInitForm($form)
 * @method onAfterInitForm($form)
 * @method onBeforeValidateForm($form)
 * @method onBeforeProcessForm($form)
 * @method onSuccess($form)
 */
abstract class BaseForm extends Control
{
	protected Form $form;
	protected bool $isAjax = true;
	protected bool $emptyHiddenToggleControls = true;

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
		$this->paramResolvers[] = function(string $type, object|array|null $values) {
			if ($type === Form::class || is_subclass_of($type, Form::class)) {
				return $this->form;
			} elseif ($type === Presenter::class || is_subclass_of($type, Presenter::class)) {
				return $this->presenter;
			} elseif ($values) {
				if ($type === ArrayHash::class) {
					return $values;
				} elseif ($type === 'array') {
					return $this->convertArrayHashToArray($values);
				}
			}

			return false;
		};

		$this->monitor(Presenter::class, function() {
			$form = $this->form = $this['form'];

			/** @link BaseForm::validateFormCallback() */
			$form->onValidate[] = [$this, 'validateFormCallback'];

			/** @link BaseForm::processFormCallback() */
			$form->onSuccess[] = [$this, 'processFormCallback'];

			foreach ($this->onBeforeInitForm as $_callback) {
				$_callback($form);
			}
			if (method_exists($this, 'onBeforeInitForm')) {
				$this->onBeforeInitForm($form);
			}

			if (!method_exists($this, 'initForm')) {
				throw new Exception('Please define the "initForm($form)" method.');
			}
			$this->invokeHandler([$this, 'initForm']);

			foreach ($this->onAfterInitForm as $_callback) {
				$_callback($form);
			}
			if (method_exists($this, 'onAfterInitForm')) {
				$this->onAfterInitForm($form);
			}

			if ($form->isSubmitted()) {
				if (is_bool($form->isSubmitted()) || $form->isSubmitted()->isDisabled()) {
					throw new Exception('The form must be submitted using the specific submit button.');
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
		// pridal jsem kvuli sobit pokladne, kde jsme meli ajax select a kde nam to na
		// $validItems = $this->getAjaxEntity()->formatValues($this->getAjaxEntity()->hydrateValues($validValues, $this->getForm()->getValues('array')));
		// hazelo
		// Nette\Forms\Container::getValues() invoked but the form is not valid (form 'form')
		// primarni problem je ten, ze $form->getUntrustedValues() vraci hodnoty jen z inputu
		// ktere uz jsou vykreslene a tudiz tam jde treba jen pulka hodnot
		// aby nemusely byt submit buttony definovane pred tim ajax selectem, tak jeste pridavame
		// !$form->isSubmitted() instanceof SubmitterControl::class
		if (!$form->isSubmitted() instanceof SubmitterControl || $form->isSubmitted()->getValidationScope() !== null) {
			return;
		}

		$this->onBeforeValidateForm($form);

		if ($form->isValid() && method_exists($this, 'validateForm')) {
			$this->invokeHandler([$this, 'validateForm'], $form->getUntrustedValues());
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

		$this->processToggles($form, emptyValue: true);

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
		(!empty($this->getTemplateFilename()))
			? $this->getTemplateFilename()
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

	protected function createComponentForm()
	{
		return new Form();
	}

	protected function _()
	{
		return call_user_func_array([$this->form->getTranslator(), 'translate'], func_get_args());
	}

	public function setOnBeforeInitForm(callable $onBeforeInitForm): static
	{
		$this->onBeforeInitForm[] = $onBeforeInitForm;
		return $this;
	}

	public function setOnAfterInitForm(callable $onAfterInitForm): static
	{
		$this->onAfterInitForm[] = $onAfterInitForm;
		return $this;
	}

	public function setOnBeforeValidateForm(callable $onBeforeValidateForm): static
	{
		$this->onBeforeValidateForm[] = $onBeforeValidateForm;
		return $this;
	}

	public function setOnBeforeProcessForm(callable $onBeforeProcessForm): static
	{
		$this->onBeforeProcessForm[] = $onBeforeProcessForm;
		return $this;
	}

	public function setOnSuccess(callable $onSuccess): static
	{
		$this->onSuccess[] = $onSuccess;
		return $this;
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function invokeHandler(callable $handler, object|array|null $formValues = null)
	{
		$types = array_map(function(ReflectionParameter $param) {
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

		return $handler(...$params);
	}

	protected function processToggles(Form $form, bool $emptyValue): void
	{
		if ($this->emptyHiddenToggleControls) {
			$toggles = $form->getToggles();
			foreach ($form->getGroups() as $_group) {
				$toggleName = '';
				foreach (explode('_', (string)$_group->getOption('label')) as $_togglePart) {
					$toggleName = trim($toggleName . '_' . $_togglePart, '_');
					if (isset($toggles[$toggleName]) && $toggles[$toggleName] === false) {
						foreach ($_group->getControls() as $_control) {
							$_control->setOption('hidden', true);
							if ($emptyValue) {
								$_control->setValue(null);
							}
						}
					}
				}
			}
		}
	}

	protected function getTemplateFilename(): ?string
	{
		return null;
	}

	protected function convertArrayHashToArray($data)
	{
		if ($data instanceof ArrayHash) {
			$data = (array) $data;
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->convertArrayHashToArray($value);
			}
		}

		return $data;
	}
}
