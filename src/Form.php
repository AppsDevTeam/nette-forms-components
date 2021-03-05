<?php

namespace ADT\Forms;

use ADT\Forms\Container;

class Form extends \Nette\Forms\Form implements Container
{
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
