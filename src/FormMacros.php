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
		
		$me->addMacro('formPair', fn(MacroNode $node, PhpWriter $writer) => $writer->write(
			'$formOrContainer = end($this->global->formsStack);'
			. '$formRenderer = $formOrContainer->getForm()->renderer;'
			. '$__formPair = is_object(%node.word) ? %node.word : $formOrContainer[%node.word];'
			. '$attrs = %node.array;'
			. '$originalWrapper = $formRenderer->wrappers["pair"]["container"];'
			. 'if ($attrs) $formRenderer->wrappers["pair"]["container"] = "div " . str_replace("=", "=\"", urldecode(http_build_query($attrs, "", "\", "))) . "\"";'
			. 'echo $formRenderer->renderPair(is_object($__formPair) ? $__formPair : $formOrContainer[$__formPair]);'
			. 'if ($attrs) $formRenderer->wrappers["pair"]["container"] = $originalWrapper;',
		));
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
			return $writer->write("echo \$ʟ_input->getForm()->getRenderer()->renderErrors(\$ʟ_input) /* line $node->startLine */;");
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
