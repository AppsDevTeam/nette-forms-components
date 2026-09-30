<?php

/**
 * @testCase
 */

declare(strict_types=1);

namespace Tests\Controls;

use ADT\Forms\Controls\PasswordRevealInput;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;
use Tests\HeaderResponse;

require __DIR__ . '/../bootstrap.php';

final class PasswordRevealInputTest extends TestCase
{
	private const NONCE = 'q1w2e3r4T5Y6u7i8O9p0+/==';

	public function testScriptWithoutPresenterHasNoNonce(): void
	{
		$form = new Form();
		$input = PasswordRevealInput::addPasswordReveal($form, 'password', false);

		Assert::contains('<script>', (string) $input->getControl());
	}

	public function testScriptGetsNonceFromCspHeader(): void
	{
		$input = $this->createAttachedInput(
			"script-src 'self' 'nonce-" . self::NONCE . "' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; "
			. "img-src 'self' data:; frame-ancestors 'none'; object-src 'none'; base-uri 'none';"
		);

		Assert::contains('<script nonce="' . self::NONCE . '">', (string) $input->getControl());
	}

	public function testScriptGetsNonceFromReportOnlyHeader(): void
	{
		$input = $this->createAttachedInput(null, "script-src 'self' 'nonce-" . self::NONCE . "'");

		Assert::contains('<script nonce="' . self::NONCE . '">', (string) $input->getControl());
	}

	public function testScriptWithoutNonceInCspHasNoNonce(): void
	{
		$input = $this->createAttachedInput("script-src 'self'; object-src 'none'");

		Assert::contains('<script>', (string) $input->getControl());
	}

	public function testScriptWithoutCspHasNoNonce(): void
	{
		$input = $this->createAttachedInput();

		Assert::contains('<script>', (string) $input->getControl());
	}

	private function createAttachedInput(?string $csp = null, ?string $cspReportOnly = null): PasswordRevealInput
	{
		$httpResponse = new HeaderResponse();
		if ($csp !== null) {
			$httpResponse->setHeader('Content-Security-Policy', $csp);
		}
		if ($cspReportOnly !== null) {
			$httpResponse->setHeader('Content-Security-Policy-Report-Only', $cspReportOnly);
		}

		$presenter = new class extends Presenter {
		};
		$presenter->injectPrimary(new Request(new UrlScript('https://localhost/')), $httpResponse);

		$form = new Form();
		$presenter->addComponent($form, 'form');

		return PasswordRevealInput::addPasswordReveal($form, 'password', false);
	}
}

(new PasswordRevealInputTest())->run();
