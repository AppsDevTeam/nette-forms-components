<?php

declare(strict_types=1);

// Every key of the catalog is a current default message of Nette Forms (otherwise a Nette update
// that rewords a message would silently bring the English text back) and keeps its placeholders.

use ADT\Forms\DI\NetteFormsComponentsExtension;
use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\Forms\Validator;
use Nette\Neon\Neon;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$extension = new NetteFormsComponentsExtension;
Assert::type(TranslationProviderInterface::class, $extension);
[$dir] = $extension->getTranslationResources();

$catalog = Neon::decode((string) file_get_contents($dir . '/messages.cs.neon'));
$messages = array_values(array_unique(Validator::$messages)); // select boxes share one message
$keys = array_keys($catalog);
sort($messages);
sort($keys);
Assert::same($messages, $keys);

foreach ($catalog as $english => $czech) {
	preg_match_all('#%[ds]#', $english, $expected);
	preg_match_all('#%[ds]#', $czech, $actual);
	Assert::same($expected[0], $actual[0], $english);
}
