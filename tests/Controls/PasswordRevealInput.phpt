<?php

/**
 * @testCase
 */

declare(strict_types=1);

namespace Tests\Controls;

use ADT\Forms\Controls\PasswordRevealInput;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

final class HeaderResponse implements IResponse
{
	/** @var array<string, string> */
	private array $headers = [];

	private int $code = self::S200_OK;

	public function setCode(int $code, ?string $reason = null): static
	{
		$this->code = $code;
		return $this;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function setHeader(string $name, string $value): static
	{
		$this->headers[$name] = $value;
		return $this;
	}

	public function addHeader(string $name, string $value): static
	{
		$this->headers[$name] = isset($this->headers[$name]) ? $this->headers[$name] . ', ' . $value : $value;
		return $this;
	}

	public function setContentType(string $type, ?string $charset = null): static
	{
		return $this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
	}

	public function redirect(string $url, int $code = self::S302_Found): void
	{
	}

	public function setExpiration(?string $expire): static
	{
		return $this;
	}

	public function isSent(): bool
	{
		return false;
	}

	public function getHeader(string $header): ?string
	{
		foreach ($this->headers as $name => $value) {
			if (strcasecmp($name, $header) === 0) {
				return $value;
			}
		}

		return null;
	}

	/** @return array<string, string> */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setCookie(
		string $name,
		string $value,
		$expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?string $sameSite = null,
	): static
	{
		return $this;
	}

	public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
	}
}

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
