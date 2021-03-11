<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class Inverse implements Filter
{

	use Nette\SmartObject;

	private Filter $filter;

	public function __construct(Filter $filter)
	{
		$this->filter = $filter;
	}



	public function matches(Method $method): bool
	{
		return !$this->filter->matches($method);
	}

	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return $this->filter->listAcceptedTypes();
	}

}
