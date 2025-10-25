<?php

namespace ADT\Forms;

use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\InvalidArgumentException;

class ControlGroup extends \Nette\Forms\ControlGroup
{
	use ElementsTrait;

	protected ?string $name = null;

	protected $parent;

	public function __construct($parent, ?string $name)
	{
		parent::__construct();
		$this->parent = $parent;
		$this->name = $name;
	}

	public function addGroup($parent, ?string $name): ControlGroup
	{
		$this->groups[] = $group = new ControlGroup($parent, $name);
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
}