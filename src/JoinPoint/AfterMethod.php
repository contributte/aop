<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Throwable;

class AfterMethod extends MethodInvocation implements ResultAware, ExceptionAware
{

	private mixed $result;

	private Throwable|null $exception = null;

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [], mixed $result = null, Throwable|null $exception = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);

		$this->result = $result;
		$this->exception = $exception;
	}

	public function getResult(): mixed
	{
		return $this->result;
	}

	public function getException(): ?Throwable
	{
		return $this->exception;
	}

}
