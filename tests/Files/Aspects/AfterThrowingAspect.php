<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class AfterThrowingAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterThrowing[] */
	public array $calls = [];


	#[Aop\Attributes\AfterThrowing('method(Tests\Files\Aspects\CommonService->magic)')]

	public function log(Aop\JoinPoint\AfterThrowing $after): void
	{
		$this->calls[] = $after;
	}

}
