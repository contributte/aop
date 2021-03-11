<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class ConditionalAfterReturningAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterReturning[] */
	public array $calls = [];

	/** @var mixed|false */
	public $modifyReturn = false;

	/**
	 * @Aop\Annotations\AfterReturning("method(Tests\Files\Aspects\CommonService->magic) && evaluate(this.return == 2)")
	 */
	public function log(Aop\JoinPoint\AfterReturning $after): void
	{
		$this->calls[] = $after;

		if ($this->modifyReturn !== false) {
			$after->setResult($this->modifyReturn);
		}
	}

}
