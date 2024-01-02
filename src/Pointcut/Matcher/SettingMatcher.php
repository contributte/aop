<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;
use Nette\DI\ContainerBuilder;

class SettingMatcher implements Filter
{

	use Nette\SmartObject;

	private Criteria $settings;

	private ContainerBuilder $builder;

	public function __construct(Criteria $criteria, Nette\DI\ContainerBuilder $builder)
	{
		$this->settings = $criteria;
		$this->builder = $builder;
	}

	public function matches(Method $method): bool
	{
		return $this->settings->evaluate($this->builder);
	}

	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
