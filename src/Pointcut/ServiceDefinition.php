<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Nette;
use Nette\DI\Definitions\Definition;
use ReflectionClass;
use ReflectionMethod;

/**
 * Wraps the Nette's ServiceDefinition, allowing safer manipulation and analysis.
 *
 * @property string $serviceId
 * @property array|Method[] $openMethods
 */
class ServiceDefinition
{

	use Nette\SmartObject;

	protected Definition $serviceDefinition;

	private ReflectionClass $originalType;

	/** @var Method[]|null */
	private ?array $openMethods = null;

	/** @var string[]|null */
	private ?array $typesWithing = null;

	private string $serviceId;

	public function __construct(Definition $def, string $serviceId)
	{
		$this->serviceDefinition = $def;

		if ($def->getType() === null) {
			throw new InvalidArgumentException('Given service definition has unresolved class, please specify service type explicitly.');
		}

		/** @var class-string $type */
		$type = $def->getType();
		$this->originalType = new ReflectionClass($type);
		$this->serviceId = $serviceId;
	}

	public function getServiceId(): string
	{
		return $this->serviceId;
	}

	public function getTypeReflection(): ReflectionClass
	{
		return $this->originalType;
	}

	/**
	 * @return array<string, string>
	 */
	public function getTypesWithin(): array
	{
		if ($this->typesWithing !== null) {
			return $this->typesWithing;
		}

		$class = $this->originalType->getName();

		$classParents = class_parents($class) ?: [];
		$classImplements = class_implements($class) ?: [];

		return $this->typesWithing = $classParents + $classImplements + [$class => $class];
	}

	/**
	 * @return Method[]
	 */
	public function getOpenMethods(): array
	{
		if ($this->openMethods !== null) {
			return $this->openMethods;
		}

		$this->openMethods = [];
		$type = $this->originalType;
		do {
			foreach ($type->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
				if ($method->isFinal()) {
					continue; // todo: maybe in next version
				}

				if (!isset($this->openMethods[$method->getName()])) {
					$this->openMethods[$method->getName()] = new Method($method, $this);
				}
			}
		} while ($type = $type->getParentClass());

		return $this->openMethods;
	}

	/**
	 * @return Method[]
	 */
	public function match(Filter $rule): array
	{
		$matching = [];
		foreach ($this->getOpenMethods() as $method) {
			if ($rule->matches($method)) {
				$matching[] = $method;
			}
		}

		return $matching;
	}

}
