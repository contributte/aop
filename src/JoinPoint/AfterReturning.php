<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

class AfterReturning extends MethodInvocation implements ResultAware
{

	private mixed $result;

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [], mixed $result = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);

		$this->result = $result;
	}

	public function setResult(mixed $result): void
	{
		$this->result = $result;
	}

	public function getResult(): mixed
	{
		return $this->result;
	}

}
