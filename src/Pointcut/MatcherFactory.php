<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Pointcut\Matcher\ClassAttributedWithMatcher;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Contributte\Aop\Pointcut\Matcher\EvaluateMatcher;
use Contributte\Aop\Pointcut\Matcher\FilterMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodAttributedWithMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodMatcher;
use Contributte\Aop\Pointcut\Matcher\SettingMatcher;
use Contributte\Aop\Pointcut\Matcher\WithinMatcher;
use Nette;
use Nette\DI\ContainerBuilder;

class MatcherFactory
{

	use Nette\SmartObject;

	private ContainerBuilder $builder;

	/** @var mixed[] */
	private array $cache = [];

	public function __construct(Nette\DI\ContainerBuilder $builder)
	{
		$this->builder = $builder;
	}



	/**
	 * @param mixed|string $arg
	 */
	public function getMatcher(string $type, $arg): Filter
	{
		if (!isset($this->cache[$type][(string) $arg])) {
			$callable = [$this, 'create' . ucfirst($type)];

			if (is_callable($callable)) {
				$this->cache[$type][(string) $arg] = call_user_func($callable, $arg);
			}
		}

		return $this->cache[$type][(string) $arg];
	}



	public function createClass(string $class): WithinMatcher
	{
		return new WithinMatcher($class);
	}



	public function createMethod(string $method): MethodMatcher
	{
		return new MethodMatcher($method);
	}



	public function createArguments(Criteria $criteria): EvaluateMatcher
	{
		return new EvaluateMatcher($criteria, $this->builder);
	}



	public function createWithin(string $within): WithinMatcher
	{
		return new WithinMatcher($within);
	}



	public function createFilter(string $filterClass): FilterMatcher
	{
		return new FilterMatcher($filterClass);
	}



	public function createSetting(Criteria $setting): SettingMatcher
	{
		return new SettingMatcher($setting, $this->builder);
	}



	public function createEvaluate(Criteria $evaluate): EvaluateMatcher
	{
		return new EvaluateMatcher($evaluate, $this->builder);
	}



	public function createClassAttributedWith(string $attributte): ClassAttributedWithMatcher
	{
		return new ClassAttributedWithMatcher($attributte);
	}



	public function createMethodAttributedWith(string $attribute): MethodAttributedWithMatcher
	{
		return new MethodAttributedWithMatcher($attribute);
	}

}
