<?php

namespace ADT\Forms;

use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\InvalidArgumentException;

class ControlGroup extends \Nette\Forms\ControlGroup
{
	use ElementsTrait;

	protected ?string $name = null;

	/** @var ControlGroup[] */
	protected array $ancestorGroups;

	public function __construct(array $ancestorGroups, ?string $name)
	{
		parent::__construct();
		$this->ancestorGroups = $ancestorGroups;
		$this->name = $name;
	}

	public function addGroup(array $ancestorGroups, ?string $name): ControlGroup
	{
		$this->groups[] = $group = new ControlGroup($ancestorGroups, $name);
		return $group;
	}
	
	public function getName(): ?string
	{
		return $this->name;
	}

	public function add(...$items): static
	{
		foreach ($items as $item) {
			if ($item instanceof Control) {
				$item->setOption('group', $this);
				$this->controls[$item] = null;

			} elseif ($item instanceof Container) {
				if ($item->getParent() instanceof DynamicContainer) {
					continue;
				}
				$this->controls[$item] = null;

			} else {
				$type = get_debug_type($item);
				throw new InvalidArgumentException("Control or Container items expected, $type given.");
			}
		}

		return $this;
	}

	public function getComponents(): array
	{
		return $this->getControls();
	}
	
	public function isControlInvalid(): bool
	{
		foreach (array_merge([$this], $this->ancestorGroups) as $_group) {
			if ($_group->getOption('isControlInvalid')) {
				return true;
			}
		}
		return false;
	}
	
	public function getAncestorGroups(): array
	{
		return $this->ancestorGroups;
	}
}