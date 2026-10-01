<?php

/**
 * @testCase
 */

declare(strict_types=1);

namespace Tests;

use ADT\Forms\BootstrapFormRenderer;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

final class BootstrapFormRendererTest extends TestCase
{
	public function testControlErrorContainerPointsToControl(): void
	{
		[$form, $renderer] = $this->createForm();
		$form['password']->addError('Heslo je slabe.');

		$html = $renderer->renderErrors($form['password']);

		Assert::contains('id="snippet-frm-password-errors"', $html);
		Assert::contains('data-adt-errors-for="frm-password"', $html);
		Assert::contains('class="invalid-feedback"', $html);
		Assert::contains('Heslo je slabe.', $html);
	}

	public function testControlErrorContainerWithoutErrorsKeepsAttribute(): void
	{
		// Prazdny kontejner se vykresluje kvuli AJAXu, JS podle nej musi poznat i "bez chyby".
		[$form, $renderer] = $this->createForm();

		$html = $renderer->renderErrors($form['password']);

		Assert::contains('id="snippet-frm-password-errors"', $html);
		Assert::contains('data-adt-errors-for="frm-password"', $html);
		Assert::notContains('<script', $html);
	}

	public function testMultipleErrorsAreAllRendered(): void
	{
		[$form, $renderer] = $this->createForm();
		$form['password']->addError('Prvni chyba.');
		$form['password']->addError('Druha chyba.');

		$html = $renderer->renderErrors($form['password']);

		Assert::contains('<div>Prvni chyba.</div>', $html);
		Assert::contains('<div>Druha chyba.</div>', $html);
	}

	public function testFormErrorContainerPointsToForm(): void
	{
		[$form, $renderer] = $this->createForm();
		$form->getElementPrototype()->setAttribute('id', 'frm-signIn');
		$form->addError('Neplatne prihlaseni.');

		$html = $renderer->renderErrors();

		Assert::contains('id="snippet-frm-signIn-errors"', $html);
		Assert::contains('data-adt-errors-for="frm-signIn"', $html);
		Assert::contains('Neplatne prihlaseni.', $html);
	}

	public function testFormWithoutIdHasNoErrorContainerAttributes(): void
	{
		// Bez id neni na co odkazat, kontejner zustava jako drive bez id i bez data atributu.
		[$form, $renderer] = $this->createForm();
		$form->addError('Neplatne prihlaseni.');

		$html = $renderer->renderErrors();

		Assert::notContains('data-adt-errors-for', $html);
		Assert::notContains('id="snippet-', $html);
		Assert::notContains('<script', $html);
		Assert::contains('Neplatne prihlaseni.', $html);
	}

	public function testAttributeValueIsEscaped(): void
	{
		$renderer = new BootstrapFormRenderer(new Form());

		$html = $renderer->doRenderErrors(['Chyba'], true, 'frm-"x"');

		// Nette\Utils\Html hodnotu s uvozovkami obali apostrofy, z atributu se tedy nevylomi.
		Assert::contains("data-adt-errors-for='frm-\"x\"'", $html);
	}

	public function testInlineScriptWithoutPresenter(): void
	{
		// Bez presenteru se CSP zjistit neda, chovani zustava jako drive.
		[$form, $renderer] = $this->createForm();
		$form['password']->addError('Heslo je slabe.');

		$html = $renderer->renderErrors($form['password']);

		Assert::contains('<script>', $html);
		Assert::contains('document.getElementById("frm-password")', $html);
		Assert::contains("classList.add('is-invalid')", $html);
	}

	public function testInlineScriptWithoutCsp(): void
	{
		[$form, $renderer] = $this->createAttachedForm();
		$form['password']->addError('Heslo je slabe.');

		Assert::contains('<script>', $renderer->renderErrors($form['password']));
	}

	public function testFormErrorsRenderInlineScriptThatSkipsForm(): void
	{
		// U formulare se skript vykresli taky, ale v prohlizeci ho vypne kontrola na FORM.
		[$form, $renderer] = $this->createForm();
		$form->getElementPrototype()->setAttribute('id', 'frm-signIn');
		$form->addError('Neplatne prihlaseni.');

		$html = $renderer->renderErrors();

		Assert::contains('<script>', $html);
		Assert::contains("tagName !== 'FORM'", $html);
	}

	/**
	 * Inline skript se vynecha jen tam, kde by ho CSP stejne zablokovala.
	 * @return list<array{string, bool}> [hlavicka Content-Security-Policy, vykresli se skript]
	 */
	public function getCspCases(): array
	{
		return [
			["script-src 'self'; style-src 'self' 'unsafe-inline'", false],
			["script-src 'nonce-q1w2e3r4T5Y6u7i8O9p0+/==' 'self'", false],
			["script-src 'self' 'unsafe-inline'", true],
			// S nonce nebo hashem prohlizec 'unsafe-inline' ignoruje.
			["script-src 'self' 'unsafe-inline' 'nonce-q1w2e3r4T5Y6u7i8O9p0+/=='", false],
			["script-src 'self' 'unsafe-inline' 'sha256-abc='", false],
			["script-src 'unsafe-inline' 'strict-dynamic'", false],
			// Bez script-src plati default-src.
			["default-src 'self'", false],
			["default-src 'self' 'unsafe-inline'", true],
			// script-src ma prednost pred default-src.
			["default-src 'self'; script-src 'self' 'unsafe-inline'", true],
			["default-src 'self' 'unsafe-inline'; script-src 'self'", false],
			// script-src-elem ma prednost pred script-src.
			["script-src 'self'; script-src-elem 'self' 'unsafe-inline'", true],
			["script-src 'self' 'unsafe-inline'; script-src-elem 'self'", false],
			// Politika, ktera skripty vubec neomezuje.
			["frame-ancestors 'none'; object-src 'none'", true],
			// Nazvy direktiv i klicova slova jsou case-insensitive.
			["SCRIPT-SRC 'self' 'UNSAFE-INLINE'", true],
			// Pri opakovane direktive plati prvni vyskyt.
			["script-src 'self'; script-src 'self' 'unsafe-inline'", false],
		];
	}

	/**
	 * @dataProvider getCspCases
	 */
	public function testInlineScriptDependsOnCsp(string $csp, bool $expectScript): void
	{
		[$form, $renderer] = $this->createAttachedForm($csp);
		$form['password']->addError('Heslo je slabe.');

		$html = $renderer->renderErrors($form['password']);

		Assert::same($expectScript, str_contains($html, '<script'), $csp);
		Assert::contains('data-adt-errors-for="frm-form-password"', $html);
		Assert::contains('Heslo je slabe.', $html);
	}

	public function testReportOnlyCspDoesNotRemoveInlineScript(): void
	{
		// Report-Only nic neblokuje, skript tedy dal funguje.
		$httpResponse = new HeaderResponse();
		$httpResponse->setHeader('Content-Security-Policy-Report-Only', "script-src 'self'");
		[$form, $renderer] = $this->createAttachedForm(null, $httpResponse);
		$form['password']->addError('Heslo je slabe.');

		Assert::contains('<script>', $renderer->renderErrors($form['password']));
	}

	public function testFormErrorsWithBlockingCspHaveNoInlineScript(): void
	{
		[$form, $renderer] = $this->createAttachedForm("script-src 'self'");
		$form->addError('Neplatne prihlaseni.');

		$html = $renderer->renderErrors();

		Assert::notContains('<script', $html);
		Assert::contains('data-adt-errors-for="frm-form"', $html);
		Assert::contains('Neplatne prihlaseni.', $html);
	}

	public function testFullFormRenderWithBlockingCsp(): void
	{
		// Pri plnem (ne-AJAX) vykresleni nastavi is-invalid na controlu uz server.
		[$form] = $this->createAttachedForm("script-src 'self'");
		$form->setAction('/sign/in');
		$form['password']->addError('Heslo je slabe.');

		$html = (string) $form->__toString();

		Assert::match('~<input[^>]+name="password"[^>]+class="[^"]*\bis-invalid\b[^"]*"~', $html);
		Assert::contains('data-adt-errors-for="frm-form-password"', $html);
		Assert::notContains('<script', $html);
	}

	/** @return array{Form, BootstrapFormRenderer} */
	private function createForm(): array
	{
		$form = new Form();

		return [$form, $this->setUpRenderer($form)];
	}

	/** @return array{Form, BootstrapFormRenderer} */
	private function createAttachedForm(?string $csp = null, ?HeaderResponse $httpResponse = null): array
	{
		$httpResponse ??= new HeaderResponse();
		if ($csp !== null) {
			$httpResponse->setHeader('Content-Security-Policy', $csp);
		}

		$presenter = new class extends Presenter {
		};
		$presenter->injectPrimary(new Request(new UrlScript('https://localhost/')), $httpResponse);

		$form = new Form();
		$presenter->addComponent($form, 'form');

		return [$form, $this->setUpRenderer($form)];
	}

	private function setUpRenderer(Form $form): BootstrapFormRenderer
	{
		$renderer = new BootstrapFormRenderer($form);
		$form->setRenderer($renderer);
		$form->addPassword('password', 'Heslo');
		BootstrapFormRenderer::makeBootstrap($form);

		return $renderer;
	}
}

(new BootstrapFormRendererTest())->run();
