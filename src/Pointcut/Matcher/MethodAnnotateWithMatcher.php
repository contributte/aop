<?php

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Doctrine\Common\Annotations\Reader;
use Nette;

class MethodAnnotateWithMatcher implements Filter
{

	use Nette\SmartObject;

	/** @var string */
	private $annotationClass;

	/** @var Reader */
	private $reader;

	public function __construct($annotationClass, Reader $reader)
	{
		$this->annotationClass = $annotationClass;
		$this->reader = $reader;
	}



	public function matches(Method $method): bool
	{
		foreach ($method->getAnnotations($this->reader) as $annotation) {
			if (!$annotation instanceof $this->annotationClass) {
				continue;
			}

			return true;
		}

		return false;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return false;
	}

}
