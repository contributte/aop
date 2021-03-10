<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;

class AroundAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AroundMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	public $modifyReturn = false;

	/**
	 * @Aop\Annotations\Around("method(Tests\Files\Aspects\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		$this->calls[] = $around;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$around->setArgument($i, $val);
			}
		}

		$result = $around->proceed();

		if ($this->modifyReturn !== false) {
			$result = $this->modifyReturn;
		}

		return $result;
	}

}
