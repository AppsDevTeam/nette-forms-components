<?php

declare(strict_types=1);

namespace ADT\Forms;

use Latte;
use Latte\CompileException;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;


/**
 * Latte macros for Nette\Forms.
 *
 * - {inputError name}
 */
final class FormMacros extends MacroSet
{
	public static function install(Latte\Compiler $compiler): void
	{
		$me = new static($compiler);
		$me->addMacro('inputError', [$me, 'macroInputError']);
	}


	/********************* macros ****************d*g**/


	/**
	 * {inputError ...}
	 */
	public function macroInputError(MacroNode $node, PhpWriter $writer)
	{
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$name = $node->tokenizer->fetchWord();
		$node->replaced = true;
		if (!$name) {
			return $writer->write("echo %escape(\$ʟ_input->getError()) /* line $node->startLine */;");
		} elseif ($name[0] === '$') {
			return $writer->write(
				'$ʟ_input = is_object(%0.word) ? %0.word : end($this->global->formsStack)[%0.word];'
				. "echo \$ʟ_input->getForm()->getRenderer()->renderErrors(\$ʟ_input) /* line $node->startLine */;",
				$name
			);
		} else {
			return $writer->write("echo end(\$this->global->formsStack)[%0.word]->getForm()->getRenderer()->renderErrors(end(\$this->global->formsStack)[%0.word]) /* line $node->startLine */;", $name);
		}
	}
}
