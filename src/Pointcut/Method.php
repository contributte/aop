<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\PhpGenerator\PointcutMethod;
use Doctrine\Common\Annotations\Reader;
use Nette;

/**
 * @property-read array|string[] $typesWithin
 */
class Method
{

	use Nette\SmartObject;

	public const VISIBILITY_PUBLIC = 'public';
	public const VISIBILITY_PROTECTED = 'protected';
	public const VISIBILITY_PRIVATE = 'private';

	/** @var Nette\Reflection\Method */
	private $method;

	/** @var ServiceDefinition */
	private $serviceDefinition;

	public function __construct(Nette\Reflection\Method $method, ServiceDefinition $serviceDefinition)
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
	 * @return object[]
	 */
	public function getTypesWithin(): array
	{
		return $this->serviceDefinition->getTypesWithin();
	}



	/**
	 * @param Reader $reader
	 * @return object[]
	 */
	public function getAnnotations(Reader $reader): array
	{
		return $reader->getMethodAnnotations($this->method);
	}



	/**
	 * @return array|object[]
	 */
	public function getClassAnnotations(Reader $reader): array
	{
		return $reader->getClassAnnotations($this->serviceDefinition->getTypeReflection());
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
		return array_keys($this->method->getParameters());
	}

	public function unwrap(): Nette\Reflection\Method
	{
		return $this->method;
	}

}
