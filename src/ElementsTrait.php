<?php

namespace ADT\Forms;

use Nette\Forms\Container;

trait ElementsTrait
{	
	protected array $groups = [];
	protected ?array $elements = null;

	public function getElements(): array
	{
		if ($this->elements === null) {
			$this->elements = $this->buildElements();
		}
		return $this->elements;
	}

	private function buildElements(): array
	{
		$result = [];

		foreach ($this->getComponents() as $component) {
			if ($component->getOption('redrawHandler') === true) {
				continue;
			}

			if ($component instanceof Container) {
				$group = $component->getCurrentGroup();
			} else {
				$group = $component->getOption('group');
			}

			if ($group === null || $group === $this) {
				$result[] = $component;
			}
		}

		foreach ($this->groups as $group) {
			if (!$group->getOption('insertAfter')) {
				array_unshift($result, $group);
			} else {
				foreach ($result as $key => $el) {
					if ($el === $group->getOption('insertAfter')) {
						array_splice($result, $key + 1, 0, [$group]);
						break;
					}
				}
			}
		}

		return $result;
	}
}