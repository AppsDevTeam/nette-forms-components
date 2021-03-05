<?php

namespace ADT\Forms;

use ADT\DoctrineForms\StaticContainer;
use Nette;
use Nette\Application\UI;
use Nette\Application\UI\Presenter;
use Closure;

class DynamicContainer extends BaseContainer
{
	const NEW_PREFIX = '_new_';
	
	private StaticContainerFactory $staticContainerFactory;
	private ?Closure $formMapper = null;
	private ?Closure $entityMapper = null;
	private bool $allowAdding = true;
	private ?ToOneContainer $template = null;


	public function __construct()
	{
		$this->monitor(Presenter::class, function() {
			/** @var UI\Form|EntityForm $form */
			$form = $this->getForm();

			if (!$form->isSubmitted()) {
				return;
			}

			if ($this->getHttpData()) {
				foreach (array_keys($this->getHttpData()) as $id) {
					$this->getComponent($id); // eager initialize
				}
			}
		});
	}


	public function validate(?array $controls = NULL): void
	{
		if (
			$this->isRequired()
			&&
			!iterator_count($this->getComponents())
		) {
			$this->addError($this->getRequiredMessage());
		}
	}

	/**
	 * @param string $name
	 * @return Nette\ComponentModel\IComponent|null
	 */
	protected function createComponent($name): ?Nette\ComponentModel\IComponent
	{
		return $this[$name] = $this->toOneContainerFactory->create();
	}


	public function setStaticContainerFactory($staticContainerFactory)
	{
		$this->staticContainerFactory = $staticContainerFactory;
		return $this;
	}


	public function getTemplate(): StaticContainer
	{
		if (!$this->template) {
			$this->template = $this[static::NEW_PREFIX]->setIsTemplate(true);
		}
		return $this->template;
	}


	public function createNew(): StaticContainer
	{
		return $this[static::NEW_PREFIX . iterator_count($this->getContainers())];
	}

	/**
	 * Fill-in with values.
	 * @param  array|object  $data
	 * @return static
	 * @internal
	 */
	public function setValues($values, bool $erase = FALSE)
	{
		foreach ($values as $name => $value) {
			if ((is_array($value) || $value instanceof Traversable) && !$this->getComponent($name, FALSE)) {
				$this->createComponent($name);
			}
		}

		return parent::setValues($values, $erase);
	}


	public function isAllowAdding(): bool
	{
		return $this->allowAdding;
	}


	public function setAllowAdding(bool $allowAdding): self
	{
		$this->allowAdding = $allowAdding;
		return $this;
	}


	private function getHttpData(): array
	{
		$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Application\UI\Form'));
		$allData = $this->getForm()->getHttpData();
		return Nette\Utils\Arrays::get($allData, $path, NULL);
	}
	

	public function getContainers(): \CallbackFilterIterator
	{
		return new \CallbackFilterIterator($this->getComponents(false, ToOneContainer::class), function ($item) {
			return !$item->isTemplate();
		});
	}
}
