<?php

declare(strict_types=1);

namespace ADT\Forms;

use Latte;

final class LatteExtension extends Latte\Extension
{
	public function getTags(): array
	{
		return [
			'formPair' => [$this, 'createFormPair']
		];
	}

	public function createFormPair(Latte\Compiler\Tag $tag): Latte\Compiler\Node
	{
		// parsování obsahu značky
		$subject = $tag->parser->parseUnquotedStringOrExpression();
		$tag->parser->stream->tryConsume(',');
		$args = $tag->parser->parseArguments();

		return new Latte\Compiler\Nodes\AuxiliaryNode(
			fn(Latte\Compiler\PrintContext $context) => $context->format(
				'$formOrContainer = end($this->global->formsStack);'
				. '$formRenderer = $formOrContainer->getForm()->renderer;'
				. '$__subject = %node;'
				. '$__formPair = is_object($__subject) ? $__subject : $formOrContainer[$__subject];'
				. '$attrs = %node;'
				. '$originalWrapper = $formRenderer->wrappers["pair"]["container"];'
				. 'if ($attrs) $formRenderer->wrappers["pair"]["container"] = "div " . str_replace("=", "=\"", urldecode(http_build_query($attrs, "", "\", "))) . "\"";'
				. 'echo $formRenderer->renderPair(is_object($__formPair) ? $__formPair : $formOrContainer[$__formPair]);'
				. 'if ($attrs) $formRenderer->wrappers["pair"]["container"] = $originalWrapper;',
				$subject,
				$args
			)
		);
	}
}
