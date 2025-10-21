<?php

namespace ADT\Forms;

use Nette\Application\UI\Presenter;
use Nette\Forms\Container;
use Nette\Forms\ControlGroup;

trait SectionTrait
{
	const string GROUP_LEVEL_SEPARATOR = '__';

	protected ?array $structure = null;
	protected array $groups = [];
	protected array $nestedGroups = [];
	
	/**
	 * @throws Exception
	 */
	public function addSection(?callable $factory = null, ?string $name = null, ?BlockName $blockName = null, array $watchForRedraw = [], ?callable $onRedraw = null, array $validationScope = []): ControlGroup
	{
		if ($onRedraw && !$name) {
			throw new Exception('Name is required when onRedraw is set.');
		}

		if ($this->getCurrentGroup() !== null) {
			$name = $this->getCurrentGroup()->getOption('label') . static::GROUP_LEVEL_SEPARATOR . $name;
		}
		$group = $this->addGroup($name);
		$group->setOption('blockName', $blockName?->getName());
		$group->setOption('watchForRedraw', $watchForRedraw);
		$group->setOption('htmlId', ($this instanceof Form ? $name : $this->getName() .'-' . $name));
		$factory && $factory();
		array_pop($this->nestedGroups);
		$this->setCurrentGroup($this->nestedGroups ? end($this->nestedGroups) : null);

		if ($onRedraw) {
			$redrawHandlerName = 'redraw' . ucfirst($name);
			$redrawHandler = $this->addSubmit($redrawHandlerName)
				->setValidationScope($validationScope);
			$redrawHandler->onClick[] = function() use ($onRedraw) {
				$onRedraw();
				$this->getForm()->getParent()->redrawControl($this->getSectionName('price'));
			};
			$group->setOption('redrawHandler', $redrawHandler);
		}

		return $group;
	}

	public function getSectionName(string ...$path): string
	{
		return implode(static::GROUP_LEVEL_SEPARATOR, $path);
	}

	/**
	 * Adds fieldset group to the form.
	 */
	public function addGroup(string|\Stringable|null $caption = null, bool $setAsCurrent = true): ControlGroup
	{
		$group = new ControlGroup;
		$group->setOption('label', $caption);
		$group->setOption('visual', true);

		if ($setAsCurrent) {
			$this->setCurrentGroup($group);
		}

		return !is_scalar($caption) || isset($this->groups[$caption])
			? $this->groups[] = $group
			: $this->groups[$caption] = $group;
	}

	/**
	 * Returns all defined groups.
	 * @return ControlGroup[]
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	public function getStructure(): array
	{
		if ($this->structure === null) {
			$this->structure = $this->processGroups($this->buildComponentGroupMap());
		}

		return $this->structure;
	}

	// VYPOCET SECTION

	/**
	 * Vytvoří mapu, která přiřazuje komponenty k jejich groupám
	 */
	private function buildComponentGroupMap(): array
	{
		$map = [];

		foreach ($this->getGroups() as $group) {
			foreach ($group->getControls() as $control) {
				$map[spl_object_id($control)] = $group;
			}
		}

		return $map;
	}

	/**
	 * Zpracuje všechny groupy hierarchicky podle '__'
	 */
	private function processGroups(array $componentToGroup): array
	{
		$allGroups = [];
		$processedContainersGlobal = [];

		// Sesbíráme všechny groupy, které mají komponenty v tomto kontejneru
		foreach ($this->getGroups() as $group) {
			$groupName = $group->getOption('label') ?? '';
			$items = [];
			$processedContainers = [];

			foreach ($group->getControls() as $control) {
				// Přeskočíme hidden fieldy
				if ($control instanceof Nette\Forms\Controls\HiddenField) {
					continue;
				}

				// Kontrola: je kontrola součástí tohoto kontejneru (nebo jeho dětí)?
				if (!$this->isComponentInContainer($control, $this)) {
					continue;
				}

				$parent = $control->getParent();

				// Pokud je komponenta přímo v tomto kontejneru
				if ($parent === $this) {
					$items[$control->getName()] = [
						'name' => $control->getName(),
						'type' => 'input',
						'component' => $control
					];
				} else {
					// Komponenta je v nějakém sub-kontejneru - najdeme direct child kontejner
					$topContainer = $parent;
					while ($topContainer->getParent() !== $this) {
						$topContainer = $topContainer->getParent();
					}

					$containerId = spl_object_id($topContainer);
					if (!isset($processedContainers[$containerId])) {
						$children = $this->collectAllComponents($topContainer, $componentToGroup);
						$items[$topContainer->getName()] = [
							'name' => $topContainer->getName(),
							'type' => 'container',
							'component' => $topContainer,
							'children' => $children
						];
						$processedContainers[$containerId] = true;
						$processedContainersGlobal[$containerId] = true;
					}
				}
			}

			if (!empty($items)) {
				$allGroups[$groupName] = [
					'name' => $groupName,
					'type' => 'section',
					'section' => $group,
					'children' => $items
				];
			}
		}

		// Vytvoříme hierarchii - vnořené groupy přidáme do parent groups
		foreach ($allGroups as $groupName => $groupData) {
			$parentName = $this->getParentGroupName($groupName);

			if ($parentName !== null && isset($allGroups[$parentName])) {
				// ZMĚNA: Použij groupName jako klíč
				$allGroups[$parentName]['children'][$groupName] = &$allGroups[$groupName];
			}
		}

		// Projdeme komponenty kontejneru v původním pořadí
		$result = [];
		$processedGroups = [];

		foreach ($this->getComponents() as $component) {
			// Přeskočíme hidden fieldy
			if ($component instanceof Nette\Forms\Controls\HiddenField) {
				continue;
			}

			$group = $componentToGroup[spl_object_id($component)] ?? null;

			if ($group !== null) {
				$groupName = $group->getOption('label') ?? '';

				// Pokud je to root level group (pro tento kontejner) a ještě jsme ji nezpracovali
				if (!str_contains($groupName, static::GROUP_LEVEL_SEPARATOR) && !isset($processedGroups[$groupName])) {
					if (isset($allGroups[$groupName])) {
						// ZMĚNA: Použij groupName jako klíč
						$result[$groupName] = $allGroups[$groupName];
						$processedGroups[$groupName] = true;
					}
				}
			} else {
				// Komponenta bez groupy
				if ($component instanceof Container) {
					$containerId = spl_object_id($component);

					$childGroup = $this->getContainerChildGroup($component, $componentToGroup);

					if ($childGroup !== null) {
						$childGroupName = $childGroup->getOption('label') ?? '';
						if (!str_contains($childGroupName, static::GROUP_LEVEL_SEPARATOR) &&
							!isset($processedGroups[$childGroupName]) &&
							isset($allGroups[$childGroupName])) {
							// ZMĚNA: Použij childGroupName jako klíč
							$result[$childGroupName] = $allGroups[$childGroupName];
							$processedGroups[$childGroupName] = true;
						}
					} elseif (!isset($processedContainersGlobal[$containerId])) {
						$children = $this->collectAllComponents($component, $componentToGroup);
						$result[$component->getName()] = [
							'name' => $component->getName(),
							'type' => 'container',
							'component' => $component,
							'children' => $children
						];
					}
				} else {
					$result[$component->getName()] = [
						'name' => $component->getName(),
						'type' => 'input',
						'component' => $component
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Zkontroluje, zda je komponenta součástí daného kontejneru (nebo jeho dětí)
	 */
	private function isComponentInContainer($component, $container): bool
	{
		$parent = $component->getParent();

		while ($parent !== null) {
			if ($parent === $container) {
				return true;
			}
			$parent = $parent->getParent();
		}

		return false;
	}

	/**
	 * Zjistí, jestli děti kontejneru mají nějakou groupu
	 */
	private function getContainerChildGroup($container, array $componentToGroup): ?ControlGroup
	{
		foreach ($container->getComponents() as $component) {
			$group = $componentToGroup[spl_object_id($component)] ?? null;
			if ($group !== null) {
				return $group;
			}

			if ($component instanceof Container) {
				$childGroup = $this->getContainerChildGroup($component, $componentToGroup);
				if ($childGroup !== null) {
					return $childGroup;
				}
			}
		}

		return null;
	}

	/**
	 * Sesbírá VŠECHNY komponenty z kontejneru (rekurzivně)
	 */
	private function collectAllComponents($container, array $componentToGroup): array
	{
		$result = [];

		foreach ($container->getComponents() as $component) {
			if ($component instanceof Nette\Forms\Controls\HiddenField) {
				continue;
			}

			if ($component instanceof Container) {
				$children = $this->collectAllComponents($component, $componentToGroup);
				$result[$component->getName()] = [
					'name' => $component->getName(),
					'type' => 'container',
					'component' => $component,
					'children' => $children
				];
			} else {
				$result[$component->getName()] = [
					'name' => $component->getName(),
					'type' => 'input',
					'component' => $component
				];
			}
		}

		return $result;
	}

	/**
	 * Zjistí parent group name z group name
	 */
	private function getParentGroupName(string $groupName): ?string
	{
		$pos = strrpos($groupName, static::GROUP_LEVEL_SEPARATOR);
		if ($pos === false) {
			return null;
		}
		return substr($groupName, 0, $pos);
	}
}