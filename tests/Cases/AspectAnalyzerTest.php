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
use PHPUnit\Framework\TestCase;
use Tests\Files\Aspects\AfterAspect;
use Tests\Files\Aspects\AfterReturningAspect;
use Tests\Files\Aspects\AfterThrowingAspect;
use Tests\Files\Aspects\AroundAspect;
use Tests\Files\Aspects\BeforeAspect;
use Tests\Files\Aspects\CommonService;

class AspectAnalyzerTest extends TestCase
{

	/***
	 * @return array<int|string, array<int, array<string, array<string, array<int, Pointcut\Rules>>>>>
	 */
	public function dataAnalyze(): array
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
	public function testAnalyze(array $pointcuts, ServiceDefinition $service): void
	{
		$builder = new Nette\DI\ContainerBuilder();
		$annotationReader = new AnnotationReader();
		$matcherFactory = new Pointcut\MatcherFactory($builder, $annotationReader);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory), $annotationReader);

		$this->assertEquals($pointcuts, $analyzer->analyze($service));
	}



	/**
	 * @param string $class
	 */
	private function createDefinition($class): Pointcut\ServiceDefinition
	{
		$def = new Nette\DI\Definitions\ServiceDefinition();
		$def->setClass($class);

		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}
