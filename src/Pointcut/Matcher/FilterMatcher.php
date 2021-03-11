<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class FilterMatcher implements Filter
{

	use Nette\SmartObject;

	/** @var Filter */
	private $filter;

	public function __construct($filterClass)
	{
		if (!in_array(Filter::class, class_implements($filterClass), true)) {
			throw new InvalidArgumentException('Given class \'' . $filterClass . '\' must implement Contributte\\Aop\\Pointcut\\Filter.');
		}

		$this->filter = new $filterClass();
	}



	public function matches(Method $method): bool
	{
		return $this->filter->matches($method);
	}



	public function listAcceptedTypes(): array
	{
		return $this->filter->listAcceptedTypes();
	}

}
