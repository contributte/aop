<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class ConstructorBeforeAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\BeforeMethod[] */
	public array $calls = [];

	#[Aop\Attributes\Before('method(Tests\Files\Aspects\CommonService->__construct)')]

	public function log(Aop\JoinPoint\BeforeMethod $before): void
	{
		$this->calls[] = $before;
	}

}
