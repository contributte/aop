<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Doctrine\Common\Annotations\AnnotationReader;
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

	private AnnotationReader $annotationReader;

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [])
	{
		$this->targetObject = $targetObject;
		$this->targetMethod = $targetMethod;
		$this->arguments = $arguments;
		$this->annotationReader = new AnnotationReader();
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


	/**
	 * @param class-string $name
	 */
	public function getAnnotation(string $name): ?object
	{
		return $this->annotationReader->getMethodAnnotation($this->getTargetReflection(), $name);
	}


	/**
	 * @return object[]
	 */
	public function getAnnotations(): array
	{
		return $this->annotationReader->getMethodAnnotations($this->getTargetReflection());
	}

}
