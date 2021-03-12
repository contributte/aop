<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\PhpGenerator\PointcutMethod;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Reader;
use Nette;
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
		return $this->method->isPublic() ? self::VISIBILITY_PUBLIC
			: ($this->method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
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
	 * @return Annotation[]
	 */
	public function getAnnotations(Reader $reader): array
	{
		/** @var Annotation[] $annotations */
		$annotations = $reader->getMethodAnnotations($this->method);

		return $annotations;
	}



	/**
	 * @return Annotation[]
	 */
	public function getClassAnnotations(Reader $reader): array
	{
		/** @var Annotation[] $annotations */
		$annotations = $reader->getClassAnnotations($this->serviceDefinition->getTypeReflection());

		return $annotations;
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
