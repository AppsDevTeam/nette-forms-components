<?php

namespace ADT\Forms;

use Nette;
use Nette\Application\UI\Presenter;

class Form extends \Nette\Application\UI\Form
{
	use AnnotationsTrait;
	
	private ?BootstrapFormRenderer $renderer = null;

	public function __construct(Nette\ComponentModel\IContainer $parent = null, string $name = null)
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
}
