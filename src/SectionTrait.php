<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\HiddenField;
use Stringable;

trait SectionTrait
{
	const string GROUP_LEVEL_SEPARATOR = '__';

	protected ?array $structure = null;
	protected array $groups = [];
	protected array $nestedGroups = [];
	protected ?ControlGroup $lastSection = null;

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

		$lastComponent = $this->getForm()->getComponents();
		$lastComponent = end($lastComponent);
		$inserAfter = $this->lastSection?->getOption('insertAfter') !== $lastComponent && $this->getComponentGroup($lastComponent) === $this->getCurrentGroup() ? $lastComponent : $this->lastSection;

		$group = $this->addGroup($name);
		$group->setOption('insertAfter', $inserAfter);
		$prefixedName = $this instanceof Form ? $name : $this->getName() .'-' . $name;
		$group->setOption('blockName', $blockName?->getName());
		$group->setOption('watchForRedraw', $watchForRedraw);
		$group->setOption('htmlId', $prefixedName);
		$factory && $factory();
		$this->lastSection = $group;
		array_pop($this->nestedGroups);
		$this->setCurrentGroup($this->nestedGroups ? end($this->nestedGroups) : null);

		if ($onRedraw) {
			$redrawHandlerName = 'redraw' . ucfirst($name);
			$redrawHandler = $this->addSubmit($redrawHandlerName)
				->setOption('redrawHandler', true)
				->setValidationScope($validationScope);
			$redrawHandler->onClick[] = function() use ($onRedraw, $prefixedName) {
				$onRedraw();
				$this->getForm()->getParent()->redrawControl($prefixedName);
			};
			$group->setOption('redrawHandler', $redrawHandler);
			foreach ($watchForRedraw as $_name) {
				$this[$_name]->setHtmlAttribute('data-adt-redraw-snippet', $redrawHandler->getHtmlName());
			}
		}

		return $group;
	}

	public function getComponentGroup($component): ?ControlGroup
	{
		foreach ($this->getGroups() as $_group) {
			if (in_array($component, $_group->getControls(), true)) {
				return $_group;
			}
		}
		return null;
	}

	public function getSectionName(string ...$path): string
	{
		return implode(static::GROUP_LEVEL_SEPARATOR, $path);
	}

	/**
	 * Adds fieldset group to the form.
	 */
	public function addGroup(string|Stringable|null $caption = null, bool $setAsCurrent = true): ControlGroup
	{
		$this->nestedGroups[] = $group = new ControlGroup;
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

	public function getSections(): array
	{
		$sections = [];
		foreach ($this->getStructure() as $_el) {
			if ($_el['type'] === 'section') {
				$sections[$_el['name']] = $_el;
			}
		}
		return $sections;
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
				// Přeskočíme hidden fieldy a komponenty s redrawHandler
				if ($control instanceof HiddenField || $control->getOption('redrawHandler') === true) {
					continue;
				}

				// Kontrola: je kontrola součástí tohoto kontejneru (nebo jeho dětí)?
				if (!$this->isComponentInContainer($control, $this)) {
					continue;
				}

				$parent = $control->getParent();

				// Pokud je komponenta přímo v tomto kontejneru
				if ($parent === $this) {
					$items[] = [
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
						$items[] = [
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

			if (!empty($items) || $groupName !== '') {
				$allGroups[$groupName] = [
					'name' => $groupName,
					'type' => 'section',
					'section' => $group,
					'children' => $items
				];
			}
		}

		// Vytvoříme hierarchii - vnořené groupy vložíme do parent groups na správná místa
		foreach ($allGroups as $groupName => $groupData) {
			$parentName = $this->getParentGroupName($groupName);

			if ($parentName !== null && isset($allGroups[$parentName])) {
				// Vložíme nested groupu na správné místo podle insertAfter
				$this->insertItemAtCorrectPosition(
					$allGroups[$parentName]['children'],
					$groupData,
					$groupData['section']->getOption('insertAfter')
				);
			}
		}

		// Připravíme seznam komponent bez hidden/redraw
		$componentsList = [];
		foreach ($this->getComponents() as $component) {
			if ($component instanceof HiddenField || $component->getOption('redrawHandler') === true) {
				continue;
			}
			$componentsList[] = $component;
		}

		// Projdeme komponenty kontejneru v původním pořadí
		$result = [];
		$processedGroups = [];

		foreach ($componentsList as $component) {
			// Nejdřív zkontrolujeme, jestli před touto komponentou nemáme přidat prázdnou root groupu
			foreach ($allGroups as $groupName => $groupData) {
				if (isset($processedGroups[$groupName]) || str_contains($groupName, static::GROUP_LEVEL_SEPARATOR)) {
					continue;
				}

				$insertAfter = $groupData['section']->getOption('insertAfter');

				// Pokud má group insertAfter a odpovídá předchozí komponentě
				if ($insertAfter !== null) {
					$lastProcessedItem = empty($result) ? null : end($result);

					if ($lastProcessedItem) {
						$shouldInsert = false;

						// Kontrola zda insertAfter odpovídá poslední přidané komponentě
						if (isset($lastProcessedItem['component']) && $lastProcessedItem['component'] === $insertAfter) {
							$shouldInsert = true;
						}
						// Kontrola zda insertAfter odpovídá poslední přidané section
						elseif (isset($lastProcessedItem['section']) && $lastProcessedItem['section'] === $insertAfter) {
							$shouldInsert = true;
						}

						if ($shouldInsert) {
							$result[] = $groupData;
							$processedGroups[$groupName] = true;
						}
					}
				}
			}

			$group = $componentToGroup[spl_object_id($component)] ?? null;

			if ($group !== null) {
				$groupName = $group->getOption('label') ?? '';

				// Pokud je to root level group (pro tento kontejner) a ještě jsme ji nezpracovali
				if (!str_contains($groupName, static::GROUP_LEVEL_SEPARATOR) && !isset($processedGroups[$groupName])) {
					if (isset($allGroups[$groupName])) {
						$result[] = $allGroups[$groupName];
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
							$result[] = $allGroups[$childGroupName];
							$processedGroups[$childGroupName] = true;
						}
					} elseif (!isset($processedContainersGlobal[$containerId])) {
						$children = $this->collectAllComponents($component, $componentToGroup);
						$result[] = [
							'name' => $component->getName(),
							'type' => 'container',
							'component' => $component,
							'children' => $children
						];
					}
				} else {
					$result[] = [
						'name' => $component->getName(),
						'type' => 'input',
						'component' => $component
					];
				}
			}
		}

		// Přidáme zbylé root level groupy, které nebyly zpracované
		foreach ($allGroups as $groupName => $groupData) {
			if (!str_contains($groupName, static::GROUP_LEVEL_SEPARATOR) && !isset($processedGroups[$groupName])) {
				$result[] = $groupData;
			}
		}

		return $result;
	}

	/**
	 * Vloží item na správné místo v children podle insertAfter
	 */
	private function insertItemAtCorrectPosition(array &$children, array $newItem, $insertAfter): void
	{
		if ($insertAfter === null) {
			// Přidej na konec
			$children[] = $newItem;
			return;
		}

		// Najdi pozici prvku, za který se má vložit
		$insertPosition = null;

		foreach ($children as $index => $child) {
			$matches = false;

			// Kontrola zda insertAfter je komponenta
			if (isset($child['component']) && $child['component'] === $insertAfter) {
				$matches = true;
			}
			// Kontrola zda insertAfter je section
			elseif (isset($child['section']) && $child['section'] === $insertAfter) {
				$matches = true;
			}

			if ($matches) {
				$insertPosition = $index + 1;
				break;
			}
		}

		if ($insertPosition !== null) {
			array_splice($children, $insertPosition, 0, [$newItem]);
		} else {
			// Pokud prvek nebyl nalezen, přidej na konec
			$children[] = $newItem;
		}
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
			// Přeskočíme hidden fieldy a komponenty s redrawHandler
			if ($component instanceof HiddenField || $component->getOption('redrawHandler') === true) {
				continue;
			}

			if ($component instanceof Container) {
				$children = $this->collectAllComponents($component, $componentToGroup);
				$result[] = [
					'name' => $component->getName(),
					'type' => 'container',
					'component' => $component,
					'children' => $children
				];
			} else {
				$result[] = [
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