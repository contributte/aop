<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Aop\Annotations\After;
use Contributte\Aop\Annotations\AfterReturning;
use Contributte\Aop\Annotations\AfterThrowing;
use Contributte\Aop\Annotations\Around;
use Contributte\Aop\Annotations\Before;
use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\ServiceDefinition;
use Doctrine\Common\Annotations\AnnotationReader;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/aspect-examples.php';



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
					Before::class => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher(CommonService::class),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition(BeforeAspect::class),
		];

		$data[] = [
			[
				'log' => [
					Around::class => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher(CommonService::class),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition(AroundAspect::class),
		];

		$data[] = [
			[
				'log' => [
					AfterReturning::class => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher(CommonService::class),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition(AfterReturningAspect::class),
		];

		$data[] = [
			[
				'log' => [
					AfterThrowing::class => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher(CommonService::class),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition(AfterThrowingAspect::class),
		];

		$data[] = [
			[
				'log' => [
					After::class => new Pointcut\Rules([
						new Pointcut\Matcher\WithinMatcher(CommonService::class),
						new Pointcut\Matcher\MethodMatcher('magic'),
					]),
				],
			],
			$this->createDefinition(AfterAspect::class),
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
