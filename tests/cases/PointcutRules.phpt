<?php declare (strict_types = 1);

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
			new Pointcut\Rules([new Matcher\WithinMatcher(SmegHead::class)]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Tests\Cases\*')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('*')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher(SmegHead::class)]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher(Cat::class)]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher(Cat::class)]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\*')]),
			$this->createDefinition(CustomTemplate::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\*')]),
			$this->createDefinition(Nette\Bridges\ApplicationLatte\Template::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\WithinMatcher('Nette\Application\UI\I*')]),
			$this->createDefinition(SmegHead::class),
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
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('public injectFoo')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('protected injectFoo')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('private injectFoo')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('*Calculation')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('protected *Calculation')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('inject*')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[inject]Bar')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[?inject]Bar')]),
			$this->createDefinition(CustomTemplate::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		true,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodMatcher('[!inject]Bar')]),
			$this->createDefinition(CustomTemplate::class),
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
		$def = $this->createDefinition(MockPresenter::class);

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
			new Pointcut\Rules([new Matcher\FilterMatcher(MyPointcutFilter::class)]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\FilterMatcher(MyPointcutFilter::class)]),
			$this->createDefinition(SmegHead::class),
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
			new Pointcut\Rules([new Matcher\ClassAnnotateWithMatcher(Test::class, $reader)]),
			$this->createDefinition(SmegHead::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\ClassAnnotateWithMatcher(Test::class, $reader)]),
			$this->createDefinition(Legie::class),
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
			new Pointcut\Rules([new Matcher\MethodAnnotateWithMatcher(Test::class, $reader)]),
			$this->createDefinition(Legie::class),
		];

		$data[] = [
		false,
			new Pointcut\Rules([new Matcher\MethodAnnotateWithMatcher(Test::class, $reader)]),
			$this->createDefinition(SmegHead::class),
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
			return Nette\PhpGenerator\ClassType::from(Method::class)->newInstanceWithoutConstructor();

		} else {
			return unserialize(sprintf('O:%d:"%s":0:{}', strlen(Method::class), Method::class));
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
