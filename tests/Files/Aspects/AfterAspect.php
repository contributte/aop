<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class AfterAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterMethod[] */
	public $calls = [];

	/**
	 * @Aop\Annotations\After("method(Tests\Files\Aspects\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterMethod $after)
	{
		$this->calls[] = $after;
	}

}
