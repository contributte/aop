<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class AfterReturningAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterReturning[] */
	public $calls = [];

	public $modifyReturn = false;

	/**
	 * @Aop\Annotations\AfterReturning("method(Tests\Files\Aspects\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterReturning $after)
	{
		$this->calls[] = $after;

		if ($this->modifyReturn !== false) {
			$after->setResult($this->modifyReturn);
		}
	}

}
