<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

class AfterReturning extends MethodInvocation implements ResultAware
{

	/** @var mixed */
	private $result;

	public function __construct($targetObject, $targetMethod, $arguments = [], $result = null)
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
