<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use Contributte\Aop;
use Nette;
use RuntimeException;
use Throwable;

class AroundBlockingAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AroundMethod[] */
	public array $calls = [];

	/** @var mixed[]|false */
	public $modifyArgs = false;

	/** @var mixed|false */
	public $modifyReturn = false;

	/** @var Throwable|false */
	public $modifyThrow = false;

	/**
	 * @return mixed
	 */
	#[Aop\Attributes\Around('method(Tests\Files\Aspects\CommonService->magic)')]
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		$this->calls[] = $around;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$around->setArgument($i, $val);
			}
		}

		if ($this->modifyThrow) {
			throw new RuntimeException('Everybody is dead Dave.');
		}

		$result = null; // do not call proceed

		if ($this->modifyReturn !== false) {
			$result = $this->modifyReturn;
		}

		return $result;
	}

}
