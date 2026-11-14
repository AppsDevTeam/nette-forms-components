<?php

declare(strict_types=1);

namespace ADT\Forms\DI;

use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\DI\CompilerExtension;

/**
 * Translations of the package for contributte/translation: the default validation messages
 * of Nette Forms (lang/messages.<locale>.neon), translated by the translator of the form.
 *
 *   extensions:
 *       netteFormsComponents: ADT\Forms\DI\NetteFormsComponentsExtension
 */
final class NetteFormsComponentsExtension extends CompilerExtension implements TranslationProviderInterface
{
	/** @return list<string> */
	public function getTranslationResources(): array
	{
		return [__DIR__ . '/../lang'];
	}
}
