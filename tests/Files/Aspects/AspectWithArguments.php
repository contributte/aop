<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class AspectWithArguments
{

	use Nette\SmartObject;

	/** @var mixed[] */
	public array $args;

	public function __construct(Nette\Http\Request $httpRequest)
	{
		$this->args = func_get_args();
	}


	#[Aop\Attributes\After('method(Tests\Files\Aspects\CommonService->magic)')]

	public function log(Aop\JoinPoint\AfterMethod $after): void
	{
		// pass
	}

}
