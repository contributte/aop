<?php

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class Inverse implements Filter
{

	use Nette\SmartObject;

	/** @var Filter */
	private $filter;

	public function __construct(Filter $filter)
	{
		$this->filter = $filter;
	}



	public function matches(Method $method): bool
	{
		return !$this->filter->matches($method);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return $this->filter->listAcceptedTypes();
	}

}