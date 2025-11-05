<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\SubmitterControl;
use Nette\InvalidArgumentException;

trait SectionTrait
{
	protected ?Section $lastSection = null;
	/** @var Section[] */
	protected array $allSections = [];
	private const string NameRegexp = '#^[a-zA-Z0-9_]+$#D';
	/** @var SubmitButton[]  */
	protected array $redrawHandlers = [];

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
		$section->setWatchForRedraw($watchForRedraw);
		$section->setValidationScope($validationScope);
		$factory && $factory($section);
		$this->lastSection = $section;
		array_pop($this->getForm()->ancestorGroups);
		$this->setCurrentGroup($this->getForm()->ancestorGroups ? end($this->getForm()->ancestorGroups) : null);

		if ($section->getWatchForRedraw()) {
			$redrawHandlers = [];

			if ($section->getWatchForRedraw() instanceof SubmitterControl) {
				$_controlName = $section->getWatchForRedraw()->getName();
				$this->redrawHandlers[$_controlName] = $section->getWatchForRedraw();
				$redrawHandlers[] = $this->redrawHandlers[$_controlName];
			} else {
				foreach ($section->getWatchForRedraw() as $_control) {
					$_controlName = $_control->name;

					if (!isset($this->redrawHandlers[$_controlName])) {
						$this->redrawHandlers[$_controlName] = $this->addSubmit('_redraw' . ucfirst($_controlName));
					}
					if ($this->redrawHandlers[$_controlName]->getValidationScope() !== null) {
						$this->redrawHandlers[$_controlName]->setValidationScope(array_merge($this->redrawHandlers[$_controlName]->getValidationScope(), $section->getValidationScope()));
					} else {
						$this->redrawHandlers[$_controlName]->setValidationScope($section->getValidationScope());
					}
					$this->redrawHandlers[$_controlName]->setOption('redrawHandler', true);
					$this->redrawHandlers[$_controlName]->onClick[] = function () use ($onRedraw, $section) {
						$onRedraw && $onRedraw();
						if (!$this->getForm()->getParent()->isControlInvalid()) {
							$this->getForm()->getParent()->redrawControl($section->getHtmlId());
						}
					};

					$_control->setHtmlAttribute('data-adt-redraw-snippet', $this->redrawHandlers[$_controlName]->getHtmlName());

					$redrawHandlers[] = $this->redrawHandlers[$_controlName];
				}
			}
			
			$section->setOption('redrawHandlers', $redrawHandlers);
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