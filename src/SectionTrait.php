<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;
use Nette\InvalidArgumentException;

trait SectionTrait
{
	protected ?ControlGroup $lastSection = null;
	/** @var ControlGroup[] */
	protected array $allGroups = [];
	private const string NameRegexp = '#^[a-zA-Z0-9_]+$#D';

	/**
	 * @throws Exception
	 */
	public function addSection(?callable $factory = null, ?string $name = null, ?BlockName $blockName = null, array $watchForRedraw = [], ?callable $onRedraw = null, array $validationScope = []): ControlGroup
	{
		$lastComponent = null;
		foreach (array_reverse($this->getComponents()) as $_component) {
			// redrawHandlery jsou umele vlozene, takze nechceme, aby ovlivnovali poradi
			if ($_component->getOption('redrawHandler') === true) {
				continue;
			}

			$lastComponent = $_component;
			break;
		}
		$insertAfter = $this->lastSection?->getOption('insertAfter') !== $lastComponent && ($lastComponent instanceof Container ? $lastComponent->getCurrentGroup() : $lastComponent->getOption('group')) === $this->getCurrentGroup() ? $lastComponent : $this->lastSection;
		if ($this->getCurrentGroup()) {
			$group = $this->getCurrentGroup()->addGroup($this, $this->getForm()->ancestorGroups, $name);
		} else {
			$group = new ControlGroup($this, $this->getForm()->ancestorGroups, $name);
			$this->groups[] = $group;
		}
		if ($name) {
			if (!preg_match(self::NameRegexp, $name)) {
				throw new InvalidArgumentException("Component name must be non-empty alphanumeric string, '$name' given.");
			}
			if (isset($this->allGroups[$name])) {
				throw new Exception("Section $name already exists.");
			}
			$this->allGroups[$name] = $group;
		}
		$this->setCurrentGroup($group);
		$this->getForm()->ancestorGroups[] = $group;
		$group->setOption('insertAfter', $insertAfter);
		$group->setOption('blockName', $blockName?->getName());
		$factory && $factory();
		$this->lastSection = $group;
		array_pop($this->getForm()->ancestorGroups);
		$this->setCurrentGroup($this->getForm()->ancestorGroups ? end($this->getForm()->ancestorGroups) : null);

		if ($watchForRedraw) {
			$redrawHandler = $this->addSubmit('_redraw' . ucfirst($name));
			$redrawHandler->setValidationScope($validationScope);
			$redrawHandler->setOption('redrawHandler', true);
			$redrawHandler->onClick[] = function () use ($onRedraw, $group) {
				$onRedraw && $onRedraw();
				$group->setOption('isControlInvalid', true);
				foreach (array_merge([$group], $group->getAncestorGroups()) as $_group) {
					$this->getForm()->getParent()->redrawControl($_group->getName());
				}
			};

			$group->setOption('redrawHandler', $redrawHandler);
			foreach ($watchForRedraw as $_control) {
				$_control->setHtmlAttribute('data-adt-redraw-snippet', $redrawHandler->getHtmlName());
			}
		}

		return $group;
	}

	/**
	 * @return ControlGroup[]
	 */
	public function getSections(): array
	{
		return $this->allGroups;
	}
}