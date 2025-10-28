<?php

namespace ADT\Forms;

use Nette\Forms\Container;

trait ElementsTrait
{	
	protected array $sections = [];
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
				$section = $component->getCurrentGroup();
			} else {
				$section = $component->getOption('section');
			}

			if ($section === null || $section === $this) {
				$result[] = $component;
			}
		}

		foreach ($this->sections as $_section) {
			if (!$_section->getOption('insertAfter')) {
				array_unshift($result, $_section);
			} else {
				foreach ($result as $key => $el) {
					if ($el === $_section->getOption('insertAfter')) {
						array_splice($result, $key + 1, 0, [$_section]);
						break;
					}
				}
			}
		}

		return $result;
	}
}