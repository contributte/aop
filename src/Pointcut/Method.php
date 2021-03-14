<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\PhpGenerator\PointcutMethod;
use Nette;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * @property-read array|string[] $typesWithin
 */
class Method
{

	use Nette\SmartObject;

	public const VISIBILITY_PUBLIC = 'public';
	public const VISIBILITY_PROTECTED = 'protected';
	public const VISIBILITY_PRIVATE = 'private';

	private ReflectionMethod $method;

	private ServiceDefinition $serviceDefinition;

	public function __construct(ReflectionMethod $method, ServiceDefinition $serviceDefinition)
	{
		$this->method = $method;
		$this->serviceDefinition = $serviceDefinition;
	}



	public function getName(): string
	{
		return $this->method->getName();
	}



	public function getVisibility(): string
	{
		if ($this->method->isPublic()) {
			return self::VISIBILITY_PUBLIC;
		}

		if ($this->method->isProtected()) {
			return self::VISIBILITY_PROTECTED;
		}

		return self::VISIBILITY_PRIVATE;
	}



	public function getClassName(): string
	{
		return $this->serviceDefinition->getTypeReflection()->getName();
	}


	/**
	 * @return string[]
	 */
	public function getTypesWithin(): array
	{
		return $this->serviceDefinition->getTypesWithin();
	}



	/**
	 * @return ReflectionAttribute[]
	 */
	public function getAttributes(): array
	{
		return $this->method->getAttributes();
	}



	/**
	 * @return ReflectionAttribute[]
	 */
	public function getClassAttributes(): array
	{
		return $this->serviceDefinition->getTypeReflection()->getAttributes();
	}



	public function getServiceDefinition(): ServiceDefinition
	{
		return $this->serviceDefinition;
	}


	public function getCode(): PointcutMethod
	{
		return PointcutMethod::expandTypeHints($this->method, PointcutMethod::from($this->method));
	}


	public function getPointcutCode(): PointcutMethod
	{
		return PointcutMethod::expandTypeHints($this->method, PointcutMethod::from($this->method));
	}


	/**
	 * @return string[]
	 */
	public function getParameterNames(): array
	{
		$names = [];

		foreach ($this->method->getParameters() as $parameter) {
			$names[] = $parameter->getName();
		}

		return $names;
	}

	public function unwrap(): ReflectionMethod
	{
		return $this->method;
	}

}
