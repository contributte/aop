<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Nette;

abstract class MethodInvocation
{

	use Nette\SmartObject;

	protected object $targetObject;

	protected string $targetMethod;

	/** @var mixed[] $arguments */
	protected $arguments;

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [])
	{
		$this->targetObject = $targetObject;
		$this->targetMethod = $targetMethod;
		$this->arguments = $arguments;
	}



	public function getTargetObject(): object
	{
		return $this->targetObject;
	}



	/**
	 * @return mixed[]
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}


	public function getTargetObjectReflection(): Nette\Reflection\ClassType
	{
		return Nette\Reflection\ClassType::from($this->targetObject);
	}


	public function getTargetReflection(): Nette\Reflection\Method
	{
		return Nette\Reflection\Method::from($this->targetObject, $this->targetMethod);
	}

}
