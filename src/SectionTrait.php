<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;
use Nette\InvalidArgumentException;

trait SectionTrait
{
	protected ?Section $lastSection = null;
	/** @var Section[] */
	protected array $allSections = [];
	private const string NameRegexp = '#^[a-zA-Z0-9_]+$#D';

	/**
	 * @throws Exception
	 */
	public function addSection(?callable $factory = null, ?string $name = null, ?BlockName $blockName = null, array $watchForRedraw = [], ?callable $onRedraw = null, array $validationScope = []): Section
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
		$insertAfter = $this->lastSection?->getOption('insertAfter') !== $lastComponent && ($lastComponent instanceof Container ? $lastComponent->getCurrentGroup() : $lastComponent->getOption('section')) === $this->getCurrentGroup() ? $lastComponent : $this->lastSection;
		if ($this->getCurrentGroup()) {
			$section = $this->getCurrentGroup()->addSection($this, $this->getForm()->ancestorGroups, $name);
		} else {
			$section = new Section($this, $this->getForm()->ancestorGroups, $name);
			$this->sections[] = $section;
		}
		if ($name) {
			if (!preg_match(self::NameRegexp, $name)) {
				throw new InvalidArgumentException("Component name must be non-empty alphanumeric string, '$name' given.");
			}
			if (isset($this->allSections[$name])) {
				throw new Exception("Section $name already exists.");
			}
			$this->allSections[$name] = $section;
		}
		$this->setCurrentGroup($section);
		$this->getForm()->ancestorGroups[] = $section;
		$section->setOption('insertAfter', $insertAfter);
		$section->setOption('blockName', $blockName?->getName());
		$factory && $factory();
		$this->lastSection = $section;
		array_pop($this->getForm()->ancestorGroups);
		$this->setCurrentGroup($this->getForm()->ancestorGroups ? end($this->getForm()->ancestorGroups) : null);

		if ($watchForRedraw) {
			$redrawHandler = $this->addSubmit('_redraw' . ucfirst($name));
			$redrawHandler->setValidationScope($validationScope);
			$redrawHandler->setOption('redrawHandler', true);
			$redrawHandler->onClick[] = function () use ($onRedraw, $section) {
				$onRedraw && $onRedraw();
				$section->setOption('isControlInvalid', true);
				foreach (array_merge([$section], $section->getAncestorSections()) as $_section) {
					$this->getForm()->getParent()->redrawControl($_section->getHtmlId());
				}
			};

			$section->setOption('redrawHandler', $redrawHandler);
			foreach ($watchForRedraw as $_control) {
				$_control->setHtmlAttribute('data-adt-redraw-snippet', $redrawHandler->getHtmlName());
			}
		}

		return $section;
	}

	/**
	 * @return Section[]
	 */
	public function getSections(): array
	{
		return $this->allSections;
	}
}