<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;

trait SectionTrait
{
	const string GROUP_LEVEL_SEPARATOR = '_';

	protected array $nestedGroups = [];
	protected ?ControlGroup $lastSection = null;

	/**
	 * @throws Exception
	 */
	public function addSection(?callable $factory = null, ?string $name = null, ?BlockName $blockName = null, array $watchForRedraw = [], ?callable $onRedraw = null, array $validationScope = []): ControlGroup
	{
		if ($this->getCurrentGroup() !== null) {
			$name = $this->getCurrentGroup()->getName() . static::GROUP_LEVEL_SEPARATOR . $name;
		}

		$lastComponent = $this->getForm()->getComponents();
		$lastComponent = end($lastComponent) ?: null;
		$insertAfter = $this->lastSection?->getOption('insertAfter') !== $lastComponent && ($lastComponent instanceof Container ? $lastComponent->getCurrentGroup() : $lastComponent->getOption('group')) === $this->getCurrentGroup() ? $lastComponent : $this->lastSection;
		
		if ($this->getCurrentGroup()) {
			$group = $this->getCurrentGroup()->addGroup($this, $name);
		} else {
			$group = new ControlGroup($this, $name);
			$this->groups[] = $group;
		}
		$this->setCurrentGroup($group);
		$this->nestedGroups[] = $group;
		$group->setOption('insertAfter', $insertAfter);
		$prefixedName = $this instanceof Form ? $name : $this->getName() .'-' . $name;
		$group->setOption('blockName', $blockName?->getName());
		$group->setOption('htmlId', $prefixedName);
		$factory && $factory();
		$this->lastSection = $group;
		array_pop($this->nestedGroups);
		$this->setCurrentGroup($this->nestedGroups ? end($this->nestedGroups) : null);

		if ($watchForRedraw) {
			$redrawHandler = $this->addSubmit('_redraw' . ucfirst($name));
			$redrawHandler->setValidationScope($validationScope);
			$redrawHandler->setOption('redrawHandler', true);

			if (is_callable($onRedraw)) {
				$redrawHandler->onClick[] = function () use ($onRedraw, $prefixedName) {
					$onRedraw();
					$snippet = '';
					foreach (explode(self::GROUP_LEVEL_SEPARATOR, $prefixedName) as $_part) {
						$snippet .= $_part;
						$this->getForm()->getParent()->redrawControl($snippet);
						$snippet .= self::GROUP_LEVEL_SEPARATOR;

					}
				};
			}

			$group->setOption('redrawHandler', $redrawHandler);
			foreach ($watchForRedraw as $_control) {
				$_control->setHtmlAttribute('data-adt-redraw-snippet', $redrawHandler->getHtmlName());
			}
		}

		return $group;
	}

	public function getSectionName(string ...$path): string
	{
		return implode(static::GROUP_LEVEL_SEPARATOR, $path);
	}

	public function getSections(): array
	{
		$sections = [];
		foreach ($this->getStructure() as $_el) {
			if ($_el instanceof ControlGroup) {
				$sections[$_el->getName()] = $_el;
			}
		}
		return $sections;
	}
}