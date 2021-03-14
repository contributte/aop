<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop\Attributes\After;
use Contributte\Aop\JoinPoint\AfterMethod;
use Nette;

class AfterAspect
{

	use Nette\SmartObject;

	/** @var array|AfterMethod[] */
	public array $calls = [];

	#[After('method(Tests\Files\Aspects\CommonService->magic)')]

	public function log(AfterMethod $after): void
	{
		$this->calls[] = $after;
	}

}
