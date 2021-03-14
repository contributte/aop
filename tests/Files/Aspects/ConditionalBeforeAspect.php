<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class ConditionalBeforeAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\BeforeMethod[] */
	public array $calls = [];

	/** @var mixed[]|false */
	public $modifyArgs = false;

	#[Aop\Attributes\Before('method(Tests\Files\Aspects\CommonService->magic($argument == 1))')]

	public function log(Aop\JoinPoint\BeforeMethod $before): void
	{
		$this->calls[] = $before;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$before->setArgument($i, $val);
			}
		}
	}

}
