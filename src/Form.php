<?php

namespace ADT\Forms;

use Exception;
use Nette;
use Nette\Application\UI\Presenter;
use Nette\Forms\Container;
use Nette\Forms\ControlGroup;
use Stringable;

class Form extends Nette\Application\UI\Form
{
	use AnnotationsTrait;
	use GetComponentTrait;

	const string GROUP_LEVEL_SEPARATOR = '__';

	private ?BootstrapFormRenderer $renderer = null;
	private array $nestedGroups = [];

	public function __construct(?Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);

		$this->monitor(Presenter::class, function($presenter) {
			// must be called here because onError and onRender callbacks are set in the constructor
			$this->getRenderer();
		});
	}

	public function getRenderer(): BootstrapFormRenderer
	{
		if ($this->renderer === null) {
			$this->renderer = new BootstrapFormRenderer($this);
		}
		return $this->renderer;
	}

	/**
	 * Adds global error message.
	 * @param  string|object  $message
	 */
	public function addError($message, bool $translate = true): void
	{
		if ($translate && $this->getTranslator()) {
			$message = $this->getTranslator()->translate($message);
		}
		parent::addError($message, false);
	}

	public function build(): array
	{
		return $this->processGroups($this, $this->buildComponentGroupMap());
	}

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
	private function processGroups($form, array $componentToGroup): array
	{
		$allGroups = [];
		$processedContainersGlobal = []; // Globální sledování zpracovaných kontejnerů

		// Sesbíráme všechny groupy a jejich komponenty
		foreach ($form->getGroups() as $group) {
			$groupName = $group->getOption('label') ?? '';
			$items = [];
			$processedContainers = []; // Sledujeme již zpracované kontejnery v této groupě

			foreach ($group->getControls() as $control) {
				// Přeskočíme hidden fieldy
				if ($control instanceof Nette\Forms\Controls\HiddenField) {
					continue;
				}

				$parent = $control->getParent();

				// Pokud je komponenta přímo ve formuláři
				if ($parent === $form) {
					$items[] = [
						'name' => $control->getName(),
						'type' => 'input',
						'component' => $control
					];
				} else {
					// Komponenta je v nějakém kontejneru - najdeme top-level kontejner
					$topContainer = $parent;
					while ($topContainer->getParent() !== $form) {
						$topContainer = $topContainer->getParent();
					}

					// Přidáme kontejner jen jednou
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
						$processedContainersGlobal[$containerId] = true; // Označíme globálně
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
				// Přidáme tuto groupu do parent groupy
				$allGroups[$parentName]['children'][] = &$allGroups[$groupName];
			}
		}

		// Nyní projdeme všechny komponenty formuláře v původním pořadí
		// a sestavíme výsledek
		$result = [];
		$processedGroups = [];

		foreach ($form->getComponents() as $component) {
			// Přeskočíme hidden fieldy
			if ($component instanceof Nette\Forms\Controls\HiddenField) {
				continue;
			}

			$group = $componentToGroup[spl_object_id($component)] ?? null;

			if ($group !== null) {
				$groupName = $group->getOption('label') ?? '';

				// Pokud je to root level group a ještě jsme ji nezpracovali
				if (!str_contains($groupName, static::GROUP_LEVEL_SEPARATOR) && !isset($processedGroups[$groupName])) {
					if (isset($allGroups[$groupName])) {
						$result[] = $allGroups[$groupName];
						$processedGroups[$groupName] = true;
					}
				}
			} else {
				// Komponenta bez groupy
				if ($component instanceof Container) {
					// Kontrola, zda jsme tento kontejner už nezpracovali v nějaké groupě
					$containerId = spl_object_id($component);

					// Zkontrolujeme, jestli děti tohoto kontejneru nemají nějakou groupu
					$childGroup = $this->getContainerChildGroup($component, $componentToGroup);

					if ($childGroup !== null) {
						// Děti mají groupu - zkontrolujeme, jestli už byla přidána
						$childGroupName = $childGroup->getOption('label') ?? '';
						if (!str_contains($childGroupName, static::GROUP_LEVEL_SEPARATOR) &&
							!isset($processedGroups[$childGroupName]) &&
							isset($allGroups[$childGroupName])) {
							$result[] = $allGroups[$childGroupName];
							$processedGroups[$childGroupName] = true;
						}
					} elseif (!isset($processedContainersGlobal[$containerId])) {
						// Kontejner ani jeho děti nemají groupu - přidáme ho normálně
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

		return $result;
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

			// Rekurzivně zkontrolujeme vnořené kontejnery
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
			// Přeskočíme hidden fieldy
			if ($component instanceof Nette\Forms\Controls\HiddenField) {
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

	public function addGroup(Stringable|string|null $caption = null, bool $setAsCurrent = true): ControlGroup
	{
		$this->nestedGroups[] = parent::addGroup($caption, $setAsCurrent);
		return end($this->nestedGroups);
	}

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
		$factory && $factory();
		array_pop($this->nestedGroups);
		$this->setCurrentGroup($this->nestedGroups ? end($this->nestedGroups) : null);

		if ($onRedraw) {
			$redrawHandler = 'redraw' . ucfirst($name);
			$group->setOption('redrawHandler', $redrawHandler);
			$this->addSubmit($redrawHandler)
				->setValidationScope($validationScope)
				->onClick[] = function() use ($onRedraw, $name) {
					$onRedraw();
					$this->getParent()->redrawControl($name);
				};
		}

		return $group;
	}

	public function getSectionName(string ...$path): string
	{
		return implode(static::GROUP_LEVEL_SEPARATOR, $path);
	}
}
