<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Annotations\AdviceAnnotation;
use Contributte\Aop\Exceptions\InvalidAspectExceptions;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Nette;

class AspectAnalyzer
{

	use Nette\SmartObject;

	/** @var Reader */
	private $annotationReader;

	/** @var Parser */
	private $pointcutParser;

	public function __construct(Parser $parser, ?Reader $reader = null)
	{
		$this->annotationReader = $reader ?: new AnnotationReader();
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
			if (!$annotations = $this->filterAopAnnotations($method->getAnnotations($this->annotationReader))) {
				continue;
			}

			$rules = [];
			foreach ($annotations as $annotation) {
				$rules[get_class($annotation)] = $this->pointcutParser->parse($annotation->value);
			}

			$pointcuts[$method->getName()] = $rules;
		}

		if (empty($pointcuts)) {
			throw new InvalidAspectExceptions('The aspect ' . $service->typeReflection . ' has no pointcuts defined.');
		}

		return $pointcuts;
	}



	/**
	 * @param array $annotations
	 * @return array|AdviceAnnotation[]
	 */
	private function filterAopAnnotations(array $annotations): array
	{
		return array_filter($annotations, function ($annotation) {
			return $annotation instanceof AdviceAnnotation;
		});
	}

}
