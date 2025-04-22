<?php

declare(strict_types=1);

namespace ADT\Forms;

use ADT\Forms\Controls\PhoneNumberInput;
use Nette;
use Nette\Forms\Controls\SelectBox;
use Nette\Utils\Html;
use Nette\Utils\IHtmlString;

class BootstrapFormRenderer extends Nette\Forms\Rendering\DefaultFormRenderer
{
	const VERSION_4 = 4;
	const VERSION_5 = 5;

	public static int $version = self::VERSION_5;

	public function __construct(Nette\Forms\Form $form)
	{
		$form->onError[] = function(Nette\Forms\Form $form) {
			if ($form->getPresenter()->isAjax()) {
				static::makeBootstrap($form);
			}
			static::sendErrorPayload($form);
		};

		$form->onRender[] = function(Nette\Forms\Form $form) {
			static::makeBootstrap($form);
		};

		$form->getElementPrototype()->setAttribute('data-adt-submit-form', true);

		$this->form = $form;
	}

	public function renderLabel(Nette\Forms\Control $control): Html
	{
		if ($control->getLabel() && $control->getLabel()->getHtml()) {
			return parent::renderLabel($control);
		}

		return Html::el();
	}

	public function renderControl(Nette\Forms\Control $control): Html
	{
		$body = $this->getWrapper('control container');
		if ($this->counter % 2) {
			$body->class($this->getValue('control .odd'), true);
		}
		if (!$this->getWrapper('pair container')->getName()) {
			$body->class($control->getOption('class'), true);
			$body->id = $control->getOption('id');
		}

		$description = $control->getOption('description');
		if ($description instanceof Nette\HtmlStringable) {
			$description = ' ' . $description;

		} elseif ($description != null) { // intentionally ==
			if ($control instanceof Nette\Forms\Controls\BaseControl) {
				$description = $control->translate($description);
			}
			$description = ' ' . $this->getWrapper('control description')->setText($description);

		} else {
			$description = '';
		}

		if ($control->isRequired()) {
			$description = $this->getValue('control requiredsuffix') . $description;
		}

		$prepend = $control->getOption('prepend') ?: '';
		if ($prepend instanceof Nette\HtmlStringable) {

		} elseif ($prepend != null) { // intentionally ==
			if ($control instanceof Nette\Forms\Controls\BaseControl) {
				$prepend = $control->translate($prepend);
			}
		}
		if ($prepend) {
			$prepend = '<span class="input-group-text">' . $prepend . '</span>';
		}

		$append = $control->getOption('append') ?: '';
		if ($append instanceof Nette\HtmlStringable) {

		} elseif ($append != null) { // intentionally ==
			if ($control instanceof Nette\Forms\Controls\BaseControl) {
				$append = $control->translate($append);
			}
		}
		if ($append) {
			$append = '<span class="input-group-text">' . $append . '</span>';
		}

		$inputGroupStart = $inputGroupEnd = '';
		if ($prepend || $append) {
			$inputGroupStart = '<div class="input-group">';
			$inputGroupEnd = '</div>';
		}

		$control->setOption('rendered', true);
		$el = $control->getControl();
		if ($el instanceof Html) {
			if ($el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), true);
			}
			$el->class($this->getValue('control .error'), $control->hasErrors());
		}

		$el = $body->setHtml($inputGroupStart . $prepend . $el . $append . $this->renderErrors($control) . $description . $inputGroupEnd);

		// Is this an instance of a RadioList or CheckboxList?
		if (
			$control instanceof Nette\Forms\Controls\RadioList ||
			$control instanceof Nette\Forms\Controls\CheckboxList ||
			$control instanceof Nette\Forms\Controls\Checkbox
		) {
			// Get original container
			$container = $control->getContainerPrototype();
			$container->removeChildren();

			// Create an empty Html container object
			$el = Html::el();

			// Get all the child items
			$items = $control instanceof Nette\Forms\Controls\Checkbox ? [$control] : $control->getItems();
			// For each child item, add the appropriate control part and label part after one another
			foreach($items as $key => $item) {
				$_container = clone $container;
				$_container->addHtml($control->getControlPart($key));
				$_container->addHtml($control->getLabelPart($key));
				$el->addHtml($_container);
			}

			if ($control instanceof Nette\Forms\Controls\Checkbox) {
				$_container->addHtml($this->doRenderErrors($control->getErrors(), true, $control->getHtmlId()));
			} else {
				$el->addHtml($this->doRenderErrors($control->getErrors(), true, $control->getHtmlId()));
			}
		}
		elseif ($control instanceof PhoneNumberInput) {
			$el = Html::el('div')
				->setAttribute('class', static::$version === self::VERSION_4 ? 'form-row' : 'row g-2')
				->addHtml('<div class="col-5">' . $control->getControlPart(PhoneNumberInput::CONTROL_COUNTRY_CODE)->setAttribute('class', static::$version === self::VERSION_4 ? 'form-control' : 'form-select') . '</div>')
				->addHtml('<div class="col-7">' . $control->getControlPart(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->setAttribute('class', 'form-control') . $description . $this->renderErrors($control) . '</div>');
		}

		return $el;
	}

	/**
	 * Renders validation errors (per form or per control).
	 */
	public function renderErrors(?Nette\Forms\Control $control = null, bool $own = true): string
	{
		$errors = $control
			? $control->getErrors()
			: ($own ? $this->form->getOwnErrors() : $this->form->getErrors());
		return $this->doRenderErrors($errors, (bool) $control, $control ? $control->getHtmlId() : $this->form->getElementPrototype()->getId());
	}

	/**
	 * We want to render erros if
	 * @param array $errors
	 * @param bool $control
	 * @param string|null $elId
	 * @return string
	 */
	public function doRenderErrors(array $errors, bool $control = false, ?string $elId = null): string
	{
		$container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
		$item = $this->getWrapper($control ? 'control erroritem' : 'error item');
		foreach ($errors as $error) {
			$item = clone $item;
			if ($error instanceof IHtmlString) {
				$item->addHtml($error);
			} else {
				$item->setText($error);
			}
			$container->addHtml($item);
		}

		if ($elId) {
			$errorElId = 'snippet-' . $elId . '-errors';
			// we want to render container for errors even if there are no errors
			// to be able to redraw it on ajax call
			$container
				->setAttribute('id', $errorElId);

			if ($errors) {
				$el = 'document.getElementById("' . $elId . '")';
				$container->addHtml("
					<script>
						if ($el.tagName !== 'FORM') {
							$el.classList.add('is-invalid'); 
							// because of radio lists and checkbox lists
							if ($el.name.endsWith('[]') || $el.type === 'radio') { 
								$el.parentNode.classList.add('is-invalid');
							}
							// because of https://github.com/twbs/bootstrap/issues/25110
							if ($el.parentNode.classList.contains('input-group')) {
								$el.parentNode.classList.add('has-validation');
							}
                        }
                    </script>
                ");
			}
		}

		return $control
			? "\n\t" . $container->render()
			: "\n" . $container->render(0);
	}

	public static function makeBootstrap(Nette\Forms\Container $container)
	{
		if (static::$version === self::VERSION_4) {
			static::bootstrap4($container);

		} elseif (static::$version === self::VERSION_5) {
			static::bootstrap5($container);

		} else {
			throw new \Exception('Unsupported Bootstrap version.');
		}
	}

	/**
	 * @throws Nette\Application\AbortException
	 */
	public static function sendErrorPayload(Nette\Application\UI\Form $form)
	{
		if ($form->getPresenter()->isAjax()) {
			$renderer = $form->getRenderer();
			$presenter = $form->getPresenter();

			$renderer->wrappers['error']['container'] = null;
			$presenter->payload->snippets['snippet-' . $form->getElementPrototype()->getAttribute('id') . '-errors'] = $renderer->renderErrors();

			$renderer->wrappers['control']['errorcontainer'] = null;
			/** @var Nette\Forms\Control $control */
			foreach ($form->getControls() as $control) {
				if ($control->getErrors()) {
					$presenter->payload->snippets['snippet-' . $control->getHtmlId() . '-errors'] = $renderer->renderErrors($control);
				}
			}

			$presenter->sendPayload();
		}
	}

	protected static function bootstrap4(Nette\Forms\Container $container): void
	{
		$renderer = $container->getForm()->getRenderer();
		$renderer->wrappers['error']['container'] = 'div';
		$renderer->wrappers['error']['item'] = 'div class="alert alert-danger"';
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['group']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['control']['.error'] = 'is-invalid';
		$renderer->wrappers['control']['.file'] = 'form-control-file';
		$renderer->wrappers['control']['errorcontainer'] = 'div class=invalid-feedback';
		$renderer->wrappers['control']['erroritem'] = 'div';
		$renderer->wrappers['control']['description'] = 'small class="form-text text-muted"';

		// we need to create a template container for DynamicContainer
		// to apply bootstrap4 styles below
		/** @var DynamicContainer $_dynamicContainer */
		foreach ($container->getComponents(true, DynamicContainer::class) as $_dynamicContainer) {
			if ($_dynamicContainer->isAllowAdding()) {
				$_dynamicContainer->getTemplate();
			}
		}

		/** @var Nette\Forms\Controls\BaseControl $control */
		foreach ($container->getControls() as $control) {
			$type = $control->getOption('type');
			if ($control instanceof Nette\Forms\Controls\Button) {
				$control->renderAsButton();
				
				if ($control->getValidationScope() !== null) {
					$control->getControlPrototype()->addClass('btn btn-outline-secondary');
				} else {
					$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-outline-secondary');
					$usedPrimary = true;
				}

			} elseif (in_array($type, ['checkbox', 'radio'], true)) {
				if ($control instanceof Nette\Forms\Controls\Checkbox) {
					$control->getLabelPrototype()->addClass('form-check-label');
				} else {
					$control->getItemLabelPrototype()->addClass('form-check-label');
				}
				$control->getControlPrototype()->addClass('form-check-input');
				$control->getContainerPrototype()->setName('div')->addClass('form-check');

			} elseif ($control instanceof PhoneNumberInput) {
				$control->getControlPrototype(PhoneNumberInput::CONTROL_COUNTRY_CODE)->addClass('form-control');
				$control->getControlPrototype(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->addClass('form-control');

			} elseif ($type !== 'hidden') {
				$control->getControlPrototype()->addClass('form-control');
			}
		}
	}

	protected static function bootstrap5(Nette\Forms\Container $container): void
	{
		static::bootstrap4($container);

		$renderer = $container->getForm()->getRenderer();
		$renderer->wrappers['pair']['container'] = 'div class=mb-3';
		$renderer->wrappers['control']['.file'] = 'form-control';

		foreach ($container->getControls() as $control) {
			$type = $control->getOption('type');

			if (!in_array($type, ['checkbox', 'radio'], true)) {
				$control->getLabelPrototype()->addClass('form-label');
			}

			if ($control instanceof SelectBox) {
				$control->getControlPrototype()->removeClass('form-control')->addClass('form-select');
			}
		}
	}
}
