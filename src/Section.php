<?php

namespace ADT\Forms;

use Exception;
use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\InvalidArgumentException;

class Section extends \Nette\Forms\ControlGroup
{
	use ElementsTrait;

	protected ?string $name = null;
	/** @var Section[] */
	protected array $ancestorSections;
	protected Container $parent;

	public function __construct(Container $parent, array $ancestorSections, ?string $name)
	{
		parent::__construct();
		$this->parent = $parent;
		$this->ancestorSections = $ancestorSections;
		$this->name = $name;
	}

	public function addSection(Container $parent, array $ancestorSections, ?string $name): Section
	{
		$this->sections[] = $section = new Section($parent, $ancestorSections, $name);
		return $section;
	}
	
	public function getName(): ?string
	{
		return $this->name;
	}

	public function getParent(): Container
	{
		return $this->parent;
	}

	/**
	 * @throws Exception
	 */
	public function getHtmlId(): string
	{
		if ($this->name === null) {
			throw new Exception('Section name is not set.');
		}

		if ($this->parent instanceof Form) {
			return $this->name;
		}

		return $this->parent->lookupPath(Form::class) . Form::GROUP_LEVEL_SEPARATOR . $this->name;
	}

	public function add(...$items): static
	{
		foreach ($items as $item) {
			if ($item instanceof Control) {
				$item->setOption('section', $this);
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
		foreach (array_merge([$this], $this->ancestorSections) as $_section) {
			if ($_section->getOption('isControlInvalid')) {
				return true;
			}
		}
		return false;
	}
	
	public function getAncestorSections(): array
	{
		return $this->ancestorSections;
	}
}