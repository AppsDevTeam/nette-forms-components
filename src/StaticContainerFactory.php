<?php

namespace ADT\Forms;

use Closure;

class StaticContainerFactory
{
	private string $name;
	private Closure $containerFactory;
	private ?string $isFilledComponentName;


	public function __construct(string $name, Closure $containerFactory, ?string $isFilledComponentName = null)
	{
		$this->name = $name;
		$this->containerFactory = $containerFactory;
		$this->isFilledComponentName = $isFilledComponentName;
	}


	public function create(): StaticContainer
	{
		return new StaticContainer($this->name, $this->containerFactory, $this->isFilledComponentName);
	}
}
