<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class FilterMatcher implements Filter
{

	use Nette\SmartObject;

	private Filter $filter;

	public function __construct(string $filterClass)
	{
		$implements = class_implements($filterClass);
		if ($implements === false || !in_array(Filter::class, $implements, true)) {
			throw new InvalidArgumentException('Given class \'' . $filterClass . '\' must implement Contributte\\Aop\\Pointcut\\Filter.');
		}

		$this->filter = new $filterClass();
	}

	public function matches(Method $method): bool
	{
		return $this->filter->matches($method);
	}

	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return $this->filter->listAcceptedTypes();
	}

}
