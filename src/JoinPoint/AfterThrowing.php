<?php

namespace Contributte\Aop\JoinPoint;

use Exception;
use Throwable;

class AfterThrowing extends MethodInvocation implements ExceptionAware
{

	/** @var Exception|Throwable */
	private $exception;

	/**
	 * @param $targetObject
	 * @param $targetMethod
	 * @param array $arguments
	 * @param Exception|Throwable|NULL $exception
	 */
	public function __construct($targetObject, $targetMethod, array $arguments = [], ?Throwable $exception = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->exception = $exception;
	}



	public function getException(): ?Throwable
	{
		return $this->exception;
	}

}
