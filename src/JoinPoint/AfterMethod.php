<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Exception;
use Throwable;

class AfterMethod extends MethodInvocation implements ResultAware, ExceptionAware
{

	/** @var mixed */
	private $result;

	/** @var Exception|Throwable|NULL */
	private $exception;

	/**
	 * @param mixed[] $arguments
	 * @param mixed|null $result
	 * @param Exception|Throwable|NULL $exception
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [], $result = null, $exception = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->result = $result;
		$this->exception = $exception;
	}



	/**
	 * @return mixed|NULL
	 */
	public function getResult()
	{
		return $this->result;
	}



	public function getException(): ?Throwable
	{
		return $this->exception;
	}

}
