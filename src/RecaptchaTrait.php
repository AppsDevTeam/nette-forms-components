<?php

namespace ADT\Forms;

use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Tracy\Debugger;

trait RecaptchaTrait
{
	/** @var RecaptchaConfig @inject */
	public RecaptchaConfig $recaptchaConfig;

	/**
	 * Automatically adds recaptcha input and validator to form on attached
	 */
	public function injectRecaptcha()
	{
		$this->setOnAfterInitForm(function (Form $form) {
			if ($this->recaptchaConfig->enabled) {
				$form->addHidden('recaptchaToken');

				$form->getElementPrototype()->setAttribute('data-adt-recaptcha', true);

				$form->onValidate[] = function (Form $form) {
					// if there are any previous errors, validation is unnecessary
					if ($form->getErrors()) {
						return;
					}

					$response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $this->recaptchaConfig->secretKey . "&response=" . urlencode($form->getValues()['recaptchaToken']) . "&remoteip=" . $_SERVER['REMOTE_ADDR']), true);
					if (!$response['success'] || $response['score'] < 0.5) {
						$form->addError($this->recaptchaConfig->errorMessage);
						Debugger::log('Recaptcha error: ' . print_r($response, true), 'recaptcha');
					}
				};
			}
		});
	}
}
