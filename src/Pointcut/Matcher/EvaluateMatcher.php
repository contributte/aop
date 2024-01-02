<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Contributte\Aop\Pointcut\RuntimeFilter;
use Nette;
use Nette\DI\ContainerBuilder;

class EvaluateMatcher implements Filter, RuntimeFilter
{

	use Nette\SmartObject;

	private Criteria $evaluate;

	private ContainerBuilder $builder;

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
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
