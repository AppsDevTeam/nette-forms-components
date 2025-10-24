<?php

namespace ADT\Forms;

use Nette\Forms\Container;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\HiddenField;

trait StructureTrait
{	
	protected array $groups = [];
	protected ?array $structure = null;

	public function getStructure(): array
	{
		if ($this->structure === null) {
			$this->structure = $this->buildStructure();
		}
		return $this->structure;
	}

	private function buildStructure(): array
	{
		$result = [];
		$processedGroups = [];
		$lastComponent = null;

		// Přidáme groups s insertAfter === null na začátek
		foreach ($this->groups as $group) {
			$insertAfter = $group->getOption('insertAfter');
			if ($insertAfter === null) {
				$result[] = $group;
				$processedGroups[spl_object_id($group)] = true;
			}
		}

		// Projdeme komponenty a přidáme je + jejich groups
		foreach ($this->getComponents() as $component) {
			// Přeskočíme hidden a redraw
			if ($component instanceof HiddenField ||
				$component->getOption('redrawHandler') === true) {
				continue;
			}

			// Před zpracováním komponenty zkontroluj, jestli nemáme přidat groupu
			foreach ($this->groups as $group) {
				$groupId = spl_object_id($group);
				if (isset($processedGroups[$groupId])) {
					continue;
				}

				$insertAfter = $group->getOption('insertAfter');
				if ($insertAfter !== null && $lastComponent === $insertAfter) {
					$result[] = $group;
					$processedGroups[$groupId] = true;
				}
			}

			// ZMĚNA: Zjisti groupu podle typu komponenty
			if ($component instanceof Container) {
				$group = $component->getCurrentGroup();
			} else {
				$group = $component->getOption('group');
			}

			if ($group !== null) {
				// Pro ControlGroup - pokud komponenta patří DO TÉTO groupy, vykresli ji
				if ($this instanceof ControlGroup && $group === $this) {
					$result[] = $component;
					$lastComponent = $component;
					continue;
				}

				// Přeskočíme komponentu (patří do jiné groupy)
				continue;
			}

			// Komponenta bez groupy
			$result[] = $component;
			$lastComponent = $component;
		}

		// Přidáme zbylé groupy
		foreach ($this->groups as $group) {
			$groupId = spl_object_id($group);
			if (!isset($processedGroups[$groupId])) {
				$result[] = $group;
			}
		}

		return $result;
	}
}