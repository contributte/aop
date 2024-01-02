<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Throwable;

class AfterThrowing extends MethodInvocation implements ExceptionAware
{

	private ?Throwable $exception;

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [], ?Throwable $exception = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);

		$this->exception = $exception;
	}

	public function getException(): ?Throwable
	{
		return $this->exception;
	}

}
