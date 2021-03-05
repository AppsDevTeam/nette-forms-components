<?php

namespace ADT\Forms\Controls;

use Closure;

class StaticContainerFactory
{
	private string $name;
	private Closure $containerFactory;
	private ?Closure $entityFactory;
	private ?string $isFilledComponentName;


	public function __construct(string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null)
	{
		$this->name = $name;
		$this->containerFactory = $containerFactory;
		$this->entityFactory = $entityFactory;
		$this->isFilledComponentName = $isFilledComponentName;
	}


	public function create(): ToOneContainer
	{
		return new ToOneContainer($this->name, $this->containerFactory, $this->entityFactory, $this->isFilledComponentName);
	}
}
