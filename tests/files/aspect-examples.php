<?php

namespace Tests\Cases;

use Contributte\Aop;
use Nette;
use RuntimeException;

class CommonService
{

	public $calls = [];

	public $throw = false;

	public $return = 2;

	public function __construct()
	{
	}

	public function magic($argument)
	{
		$this->calls[] = func_get_args();

		if ($this->throw) {
			throw new RuntimeException("Something's fucky");
		}

		return $this->return * $argument;
	}

}



interface ICommonServiceFactory
{

	/** @return CommonService */
	function create();

}



class BeforeAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\BeforeMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	/**
	 * @Aop\Before("method(Tests\Cases\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->calls[] = $before;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$before->setArgument($i, $val);
			}
		}
	}

}



class ConditionalBeforeAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\BeforeMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	/**
	 * @Aop\Before("method(Tests\Cases\CommonService->magic($argument == 1))")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->calls[] = $before;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$before->setArgument($i, $val);
			}
		}
	}

}



class SecondBeforeAspect extends BeforeAspect
{

}



class AroundAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AroundMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	public $modifyReturn = false;

	/**
	 * @Aop\Around("method(Tests\Cases\CommonService->magic)")
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



class ConditionalAroundAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AroundMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	public $modifyReturn = false;

	/**
	 * @Aop\Around("method(Tests\Cases\CommonService->magic($argument == 1))")
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



class SecondAroundAspect extends AroundAspect
{

}



class AroundBlockingAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AroundMethod[] */
	public $calls = [];

	public $modifyArgs = false;

	public $modifyReturn = false;

	public $modifyThrow = false;

	/**
	 * @Aop\Around("method(Tests\Cases\CommonService->magic)")
	 */
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



class SecondAroundBlockingAspect extends AroundBlockingAspect
{

}



class AfterReturningAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterReturning[] */
	public $calls = [];

	public $modifyReturn = false;

	/**
	 * @Aop\AfterReturning("method(Tests\Cases\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterReturning $after)
	{
		$this->calls[] = $after;

		if ($this->modifyReturn !== false) {
			$after->setResult($this->modifyReturn);
		}
	}

}



class ConditionalAfterReturningAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterReturning[] */
	public $calls = [];

	public $modifyReturn = false;

	/**
	 * @Aop\AfterReturning("method(Tests\Cases\CommonService->magic) && evaluate(this.return == 2)")
	 */
	public function log(Aop\JoinPoint\AfterReturning $after)
	{
		$this->calls[] = $after;

		if ($this->modifyReturn !== false) {
			$after->setResult($this->modifyReturn);
		}
	}

}



class SecondAfterReturningAspect extends AfterReturningAspect
{

}



class AfterThrowingAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterThrowing[] */
	public $calls = [];

	/**
	 * @Aop\AfterThrowing("method(Tests\Cases\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterThrowing $after)
	{
		$this->calls[] = $after;
	}

}



class SecondAfterThrowingAspect extends AfterThrowingAspect
{

}



class AfterAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\AfterMethod[] */
	public $calls = [];

	/**
	 * @Aop\After("method(Tests\Cases\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterMethod $after)
	{
		$this->calls[] = $after;
	}

}



class SecondAfterAspect extends AfterAspect
{

}

class AspectWithArguments
{

	use Nette\SmartObject;

	public $args;

	public function __construct(Nette\Http\Request $httpRequest)
	{
		$this->args = func_get_args();
	}



	/**
	 * @Aop\After("method(Tests\Cases\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterMethod $after)
	{
		// pass
	}

}


class ConstructorBeforeAspect
{

	use Nette\SmartObject;

	/** @var array|Aop\JoinPoint\BeforeMethod[] */
	public $calls = [];

	/**
	 * @Aop\Before("method(Tests\Cases\CommonService->__construct)")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->calls[] = $before;
	}

}