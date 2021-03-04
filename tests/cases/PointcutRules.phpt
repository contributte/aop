<?php

/**
 * Test: Contributte\Aop\PointcutRules.
 *
 * @testCase Tests\Cases\PointcutRulesTest
 */

namespace Tests\Cases;

use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Matcher;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Contributte\Aop\Pointcut\Matcher\SettingMatcher;
use Contributte\Aop\Pointcut\Method;
use Contributte\Aop\Pointcut\ServiceDefinition;
use Doctrine\Common\Annotations\AnnotationReader;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/pointcut-examples.php';



class PointcutRulesTest extends Tester\TestCase
{

	public function dataMatchWithin()
	{
		$data = [];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\SmegHead')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\*')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('*')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\SmegHead')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\Cat')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\Cat')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\*')]),
			$this->createDefinition('Tests\Cases\CustomTemplate'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\*')]),
			$this->createDefinition(Nette\Bridges\ApplicationLatte\Template::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\I*')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataMatchWithin
	 */
	public function testMatchWithin($expected, Filter $rules, ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchMethod()
	{
		$data = [];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('injectFoo')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('public injectFoo')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('protected injectFoo')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('private injectFoo')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('*Calculation')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('protected *Calculation')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('inject*')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[inject]Bar')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition('Tests\Cases\CustomTemplate'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition('Tests\Cases\CustomTemplate'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataMatchMethod
	 */
	public function testMatchMethod($expected, Filter $rules, ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function testMatchMethod_or()
	{
		$rules = new Pointcut\Rules([new Matcher\MethodMatcher('public [render|action|handle]*')]);
		$def = $this->createDefinition('Tests\Cases\MockPresenter');

		Assert::same([
			$def->openMethods['renderDefault'],
			$def->openMethods['actionDefault'],
			$def->openMethods['handleSort'],
		], $def->match($rules));
	}



	public function dataMatchFilter()
	{
		$data = [];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\FilterMatcher('Tests\Cases\MyPointcutFilter')]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\FilterMatcher('Tests\Cases\MyPointcutFilter')]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataMatchFilter
	 */
	public function testMatchFilter($expected, Filter $rules, ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchClassAnnotateWith()
	{
		$data = [];

		$reader = new AnnotationReader();

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\ClassAnnotateWithMatcher('Tests\Cases\Test', $reader)]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\ClassAnnotateWithMatcher('Tests\Cases\Test', $reader)]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataMatchClassAnnotateWith
	 */
	public function testMatchClassAnnotateWith($expected, Filter $rules, ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchMethodAnnotateWith()
	{
		$data = [];

		$reader = new AnnotationReader();

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodAnnotateWithMatcher('Tests\Cases\Test', $reader)]),
			$this->createDefinition('Tests\Cases\Legie'),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodAnnotateWithMatcher('Tests\Cases\Test', $reader)]),
			$this->createDefinition('Tests\Cases\SmegHead'),
		];

		return $data;
	}



	/**
	 * @dataProvider dataMatchMethodAnnotateWith
	 */
	public function testMatchMethodAnnotateWith($expected, Filter $rules, ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function testMatchesSetting()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo']['dave'] = true;
		$builder->parameters['foo']['kryten'] = false;
		$builder->parameters['friendship'] = 'Is magic';

		$args = new SettingMatcher(Criteria::create()->where('foo.dave', Criteria::EQ, new Code\PhpLiteral('TRUE')), $builder);
		Assert::true($args->matches($this->mockMethod()));

		$args = new SettingMatcher(Criteria::create()->where('foo.kryten', Criteria::EQ, new Code\PhpLiteral('FALSE')), $builder);
		Assert::true($args->matches($this->mockMethod()));

		$args = new SettingMatcher(Criteria::create()->where('friendship', Criteria::EQ, new Code\PhpLiteral("'Is magic'")), $builder);
		Assert::true($args->matches($this->mockMethod()));
	}



	/**
	 * @return Method
	 */
	private function mockMethod()
	{
		if (method_exists(Nette\PhpGenerator\ClassType::class, 'newInstanceWithoutConstructor')) {
			return Nette\PhpGenerator\ClassType::from('Contributte\Aop\Pointcut\Method')->newInstanceWithoutConstructor();

		} else {
			return unserialize(sprintf('O:%d:"%s":0:{}', strlen('Contributte\Aop\Pointcut\Method'), 'Contributte\Aop\Pointcut\Method'));
		}
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

(new PointcutRulesTest())->run();
