<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Pointcut\Matcher\ClassAnnotateWithMatcher;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Contributte\Aop\Pointcut\Matcher\EvaluateMatcher;
use Contributte\Aop\Pointcut\Matcher\FilterMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodAnnotateWithMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodMatcher;
use Contributte\Aop\Pointcut\Matcher\SettingMatcher;
use Contributte\Aop\Pointcut\Matcher\WithinMatcher;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Nette;
use Nette\DI\ContainerBuilder;

class MatcherFactory
{

	use Nette\SmartObject;

	private ContainerBuilder $builder;

	private Reader $annotationReader;

	/** @var mixed[] */
	private array $cache = [];

	public function __construct(Nette\DI\ContainerBuilder $builder, ?Reader $annotationReader = null)
	{
		$this->builder = $builder;
		$this->annotationReader = $annotationReader ?: new AnnotationReader();
	}



	/**
	 * @param mixed|string $arg
	 */
	public function getMatcher(string $type, $arg): Filter
	{
		if (!isset($this->cache[$type][(string) $arg])) {
			$this->cache[$type][(string) $arg] = call_user_func([$this, 'create' . ucfirst($type)], $arg);
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



	public function createClassAnnotatedWith(string $annotation): ClassAnnotateWithMatcher
	{
		return new ClassAnnotateWithMatcher($annotation, $this->annotationReader);
	}



	public function createMethodAnnotatedWith(string $annotation): MethodAnnotateWithMatcher
	{
		return new MethodAnnotateWithMatcher($annotation, $this->annotationReader);
	}

}
