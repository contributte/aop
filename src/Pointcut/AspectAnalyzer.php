<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Attributes\AdviceAttribute;
use Contributte\Aop\Exceptions\InvalidAspectExceptions;
use Nette;
use ReflectionAttribute;

class AspectAnalyzer
{

	use Nette\SmartObject;

	private Parser $pointcutParser;

	public function __construct(Parser $parser)
	{
		$this->pointcutParser = $parser;
	}

	/**
	 * @return array<string, array<string, Rules|mixed>>
	 * @throws InvalidAspectExceptions
	 */
	public function analyze(ServiceDefinition $service): array
	{
		$pointcuts = [];
		foreach ($service->getOpenMethods() as $method) {
			if (!$attributes = $this->getAopAdviceAttributes($method->getAttributes())) {
				continue;
			}

			$rules = [];
			foreach ($attributes as $attr) {
				$rules[$attr::class] = $this->pointcutParser->parse($attr->getValue());
			}

			$pointcuts[$method->getName()] = $rules;
		}

		if (empty($pointcuts)) {
			throw new InvalidAspectExceptions('The aspect ' . $service->getTypeReflection() . ' has no pointcuts defined.');
		}

		return $pointcuts;
	}

	/**
	 * @param ReflectionAttribute[] $attributes
	 * @return AdviceAttribute[]
	 */
	private function getAopAdviceAttributes(array $attributes): array
	{
		$result = [];
		foreach ($attributes as $attribute) {
			$instance = $attribute->newInstance();
			if ($instance instanceof AdviceAttribute) {
				$result[] = $instance;
			}
		}

		return $result;
	}

}
