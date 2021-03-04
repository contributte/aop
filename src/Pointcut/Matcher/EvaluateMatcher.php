<?php

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Contributte\Aop\Pointcut\RuntimeFilter;
use Nette;
use Nette\DI\ContainerBuilder;

class EvaluateMatcher implements Filter, RuntimeFilter
{

	use Nette\SmartObject;

	/** @var Criteria */
	private $evaluate;

	/** @var ContainerBuilder */
	private $builder;

	public function __construct(Criteria $criteria, Nette\DI\ContainerBuilder $builder)
	{
		$this->evaluate = $criteria;
		$this->builder = $builder;
	}



	public function matches(Method $method): bool
	{
		return true;
	}



	public function createCondition(): ?Nette\PhpGenerator\Literal
	{
		return $this->evaluate->serialize($this->builder);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return false;
	}

}
