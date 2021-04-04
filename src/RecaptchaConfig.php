<?php

namespace ADT\Forms;

class RecaptchaConfig
{
	public string $secretKey;
	public string $errorMessage;
	public bool $enabled = TRUE;

	public function __construct(string $secretKey, string $errorMessage)
	{
		$this->secretKey = $secretKey;
		$this->errorMessage = $errorMessage;
	}

	public function setEnabled(bool $enabled = TRUE): void {
		$this->enabled = $enabled;
	}

}
