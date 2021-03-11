<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Pointcut\Matcher\ClassAnnotateWithMatcher;
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

	/** @var ContainerBuilder */
	private $builder;

	/** @var Reader */
	private $annotationReader;

	/** @var array */
	private $cache = [];

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



	public function createMethod($method): MethodMatcher
	{
		return new MethodMatcher($method);
	}



	public function createArguments($criteria): EvaluateMatcher
	{
		return new EvaluateMatcher($criteria, $this->builder);
	}



	public function createWithin($within): WithinMatcher
	{
		return new WithinMatcher($within);
	}



	public function createFilter($filterClass): FilterMatcher
	{
		return new FilterMatcher($filterClass);
	}



	public function createSetting($setting): SettingMatcher
	{
		return new SettingMatcher($setting, $this->builder);
	}



	public function createEvaluate($evaluate): EvaluateMatcher
	{
		return new EvaluateMatcher($evaluate, $this->builder);
	}



	public function createClassAnnotatedWith($annotation): ClassAnnotateWithMatcher
	{
		return new ClassAnnotateWithMatcher($annotation, $this->annotationReader);
	}



	public function createMethodAnnotatedWith($annotation): MethodAnnotateWithMatcher
	{
		return new MethodAnnotateWithMatcher($annotation, $this->annotationReader);
	}

}
