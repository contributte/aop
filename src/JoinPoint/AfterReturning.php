<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

class AfterReturning extends MethodInvocation implements ResultAware
{

	/** @var mixed */
	private $result;

	/**
	 * @param mixed[] $arguments
	 * @param mixed|object $result
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [], $result = null)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->result = $result;
	}



	/**
	 * @param mixed $result
	 */
	public function setResult($result): void
	{
		$this->result = $result;
	}



	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}

}
