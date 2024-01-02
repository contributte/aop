<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Nette;
use ReflectionClass;
use ReflectionMethod;

abstract class MethodInvocation
{

	use Nette\SmartObject;

	protected object $targetObject;

	protected string $targetMethod;

	/** @var mixed[] $arguments */
	protected array $arguments;

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

	public function getTargetObjectReflection(): ReflectionClass
	{
		return new ReflectionClass($this->targetObject);
	}

	public function getTargetReflection(): ReflectionMethod
	{
		return new ReflectionMethod($this->targetObject, $this->targetMethod);
	}

}
