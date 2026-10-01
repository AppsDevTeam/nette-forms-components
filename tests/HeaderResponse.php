<?php

declare(strict_types=1);

namespace Tests;

use Nette\Http\IResponse;

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
