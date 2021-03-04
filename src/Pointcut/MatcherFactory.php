<?php

namespace Contributte\Aop\Pointcut;

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
	 * @param mixed $arg
	 * @return Filter
	 */
	public function getMatcher(string $type, $arg)
	{
		if (!isset($this->cache[$type][(string) $arg])) {
			$this->cache[$type][(string) $arg] = call_user_func([$this, 'create' . ucfirst($type)], $arg);
		}

		return $this->cache[$type][(string) $arg];
	}



	public function createClass(string $class): WithinMatcher
	{
		return new Matcher\WithinMatcher($class);
	}



	public function createMethod($method)
	{
		return new Matcher\MethodMatcher($method);
	}



	public function createArguments($criteria)
	{
		return new Matcher\EvaluateMatcher($criteria, $this->builder);
	}



	public function createWithin($within)
	{
		return new Matcher\WithinMatcher($within);
	}



	public function createFilter($filterClass)
	{
		return new Matcher\FilterMatcher($filterClass);
	}



	public function createSetting($setting)
	{
		return new Matcher\SettingMatcher($setting, $this->builder);
	}



	public function createEvaluate($evaluate)
	{
		return new Matcher\EvaluateMatcher($evaluate, $this->builder);
	}



	public function createClassAnnotatedWith($annotation)
	{
		return new Matcher\ClassAnnotateWithMatcher($annotation, $this->annotationReader);
	}



	public function createMethodAnnotatedWith($annotation)
	{
		return new Matcher\MethodAnnotateWithMatcher($annotation, $this->annotationReader);
	}

}
