<?php

namespace Tests\Cases;

use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\ServiceDefinition;
use Doctrine\Common\Annotations\AnnotationReader;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/aspect-examples.php';
require_once __DIR__ . '/../../src/annotations.php';



class AspectAnalyzerTest extends Tester\TestCase
{

	/***
	 * @return array
	 */
	public function dataAnalyze()
	{
		$data = [];

		$data[] = [
			[
				'log' => [
					'Contributte\Aop\Before' => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher('Tests\Cases\CommonService'),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition('Tests\Cases\BeforeAspect'),
		];

		$data[] = [
			[
				'log' => [
					'Contributte\Aop\Around' => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher('Tests\Cases\CommonService'),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition('Tests\Cases\AroundAspect'),
		];

		$data[] = [
			[
				'log' => [
					'Contributte\Aop\AfterReturning' => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher('Tests\Cases\CommonService'),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition('Tests\Cases\AfterReturningAspect'),
		];

		$data[] = [
			[
				'log' => [
					'Contributte\Aop\AfterThrowing' => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher('Tests\Cases\CommonService'),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition('Tests\Cases\AfterThrowingAspect'),
		];

		$data[] = [
			[
				'log' => [
					'Contributte\Aop\After' => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher('Tests\Cases\CommonService'),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition('Tests\Cases\AfterAspect'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataAnalyze
	 */
	public function testAnalyze(array $pointcuts, ServiceDefinition $service)
	{
		$builder = new Nette\DI\ContainerBuilder();
		$annotationReader = new AnnotationReader();
		$matcherFactory = new Pointcut\MatcherFactory($builder, $annotationReader);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory), $annotationReader);

		Assert::equal($pointcuts, $analyzer->analyze($service));
	}



	/**
	 * @param string $class
	 * @return Pointcut\ServiceDefinition
	 */
	private function createDefinition($class)
	{
		$def = new Nette\DI\ServiceDefinition();
		$def->setClass($class);

		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}

(new AspectAnalyzerTest())->run();
