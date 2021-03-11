<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

use Nette;

class CustomTemplate implements Nette\Application\UI\ITemplate
{

	public function render(): void
	{
	}


	public function setFile(string $file): void
	{
	}


	public function getFile(): ?string
	{
	}

}
