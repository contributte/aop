<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Doctrine\Common\Annotations\Reader;
use Nette;

class ClassAnnotateWithMatcher implements Filter
{

	use Nette\SmartObject;

	private string $annotationClass;

	private Reader $reader;

	public function __construct(string $annotationClass, Reader $reader)
	{
		$this->annotationClass = $annotationClass;
		$this->reader = $reader;
	}



	public function matches(Method $method): bool
	{
		foreach ($method->getClassAnnotations($this->reader) as $annotation) {
			if (!$annotation instanceof $this->annotationClass) {
				continue;
			}

			return true;
		}

		return false;
	}



	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
