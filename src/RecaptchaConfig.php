<?php

namespace ADT\Forms;

class RecaptchaConfig
{
	public string $secretKey;
	public string $errorMessage;
	public bool $enabled = true;

	public function __construct(string $secretKey, string $errorMessage, bool $enabled = true)
	{
		$this->secretKey = $secretKey;
		$this->errorMessage = $errorMessage;
		$this->enabled = $enabled;
	}

	public function setEnabled(bool $enabled = true): void 
	{
		$this->enabled = $enabled;
	}
}
