<?php declare(strict_types = 1);

/**
 * Test: Contributte\Aop\PointcutRules.
 *
 * @testCase Tests\Cases\PointcutRulesTest
 */

namespace Tests\Cases;

use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Matcher\ClassAnnotateWithMatcher;
use Contributte\Aop\Pointcut\Matcher\FilterMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodAnnotateWithMatcher;
use Contributte\Aop\Pointcut\Matcher\MethodMatcher;
use Contributte\Aop\Pointcut\Matcher\SettingMatcher;
use Contributte\Aop\Pointcut\Matcher\WithinMatcher;
use Contributte\Aop\Pointcut\Method;
use Contributte\Aop\Pointcut\Rules;
use Doctrine\Common\Annotations\AnnotationReader;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use PHPUnit\Framework\TestCase;
use Tests\Files\Pointcut\Cat;
use Tests\Files\Pointcut\CustomTemplate;
use Tests\Files\Pointcut\Legie;
use Tests\Files\Pointcut\MockPresenter;
use Tests\Files\Pointcut\MyPointcutFilter;
use Tests\Files\Pointcut\SmegHead;
use Tests\Files\Pointcut\Test;

class PointcutRulesTest extends TestCase
{

	/**
	 * @return array<int|string, array<int, bool|Rules>>
	 */
	public function dataMatchWithin(): array
	{
		$data = [];
		$data[] = [true, new Rules([new WithinMatcher(SmegHead::class)]), $this->createDefinition(SmegHead::class)];
		$data[] = [true, new Rules([new WithinMatcher('Tests\Files\Pointcut\*')]), $this->createDefinition(SmegHead::class)];
		$data[] = [true, new Rules([new WithinMatcher('*')]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new WithinMatcher(SmegHead::class)]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new WithinMatcher(Cat::class)]), $this->createDefinition(Legie::class)];
		$data[] = [false, new Rules([new WithinMatcher(Cat::class)]), $this->createDefinition(SmegHead::class)];
		$data[] = [true, new Rules([new WithinMatcher('Nette\Application\UI\*')]), $this->createDefinition(CustomTemplate::class)];
		$data[] = [true, new Rules([new WithinMatcher('Nette\Application\UI\*')]), $this->createDefinition(Template::class)];
		$data[] = [false, new Rules([new WithinMatcher('Nette\Application\UI\I*')]), $this->createDefinition(SmegHead::class)];
		return $data;
	}

	/**
	 * @dataProvider dataMatchWithin
	 */
	public function testMatchWithin(bool $expected, Pointcut\Filter $rules, Pointcut\ServiceDefinition $def): void
	{
		$this->assertSame($expected, (bool) $def->match($rules));
	}


	/**
	 * @return array<int|string, array<int, bool|Rules>>
	 */
	public function dataMatchMethod(): array
	{
		$data = [];
		$data[] = [true, new Rules([new MethodMatcher('injectFoo')]), $this->createDefinition(SmegHead::class)];
		$data[] = [true, new Rules([new MethodMatcher('public injectFoo')]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new MethodMatcher('protected injectFoo')]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new MethodMatcher('private injectFoo')]), $this->createDefinition(SmegHead::class)];
		$data[] = [true, new Rules([new MethodMatcher('*Calculation')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('protected *Calculation')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('inject*')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('[inject]Bar')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('[?inject]Bar')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('[?inject]Bar')]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new MethodMatcher('[?inject]Bar')]), $this->createDefinition(CustomTemplate::class)];
		$data[] = [false, new Rules([new MethodMatcher('[!inject]Bar')]), $this->createDefinition(Legie::class)];
		$data[] = [true, new Rules([new MethodMatcher('[!inject]Bar')]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new MethodMatcher('[!inject]Bar')]), $this->createDefinition(CustomTemplate::class)];
		return $data;
	}

	/**
	 * @dataProvider dataMatchMethod
	 */
	public function testMatchMethod(bool $expected, Filter $rules, Pointcut\ServiceDefinition $def): void
	{
		$this->assertSame($expected, (bool) $def->match($rules));
	}

	public function testMatchMethod_or(): void
	{
		$rules = new Rules([new MethodMatcher('public [render|action|handle]*')]);
		$def = $this->createDefinition(MockPresenter::class);
		$this->assertSame([$def->openMethods['renderDefault'], $def->openMethods['actionDefault'], $def->openMethods['handleSort']], $def->match($rules));
	}


	/**
	 * @return array<int|string, array<int, bool|Rules>>
	 */
	public function dataMatchFilter(): array
	{
		$data = [];
		$data[] = [true, new Rules([new FilterMatcher(MyPointcutFilter::class)]), $this->createDefinition(Legie::class)];
		$data[] = [false, new Rules([new FilterMatcher(MyPointcutFilter::class)]), $this->createDefinition(SmegHead::class)];
		return $data;
	}
	/**
	 * @dataProvider dataMatchFilter
	 */
	public function testMatchFilter(bool $expected, Filter $rules, Pointcut\ServiceDefinition $def): void
	{
		$this->assertSame($expected, (bool) $def->match($rules));
	}

	/**
	 * @return array<int|string, array<int, bool|Rules>>
	 */
	public function dataMatchClassAnnotateWith(): array
	{
		$data = [];
		$reader = new AnnotationReader();
		$data[] = [true, new Rules([new ClassAnnotateWithMatcher(Test::class, $reader)]), $this->createDefinition(SmegHead::class)];
		$data[] = [false, new Rules([new ClassAnnotateWithMatcher(Test::class, $reader)]), $this->createDefinition(Legie::class)];
		return $data;
	}
	/**
	 * @dataProvider dataMatchClassAnnotateWith
	 */
	public function testMatchClassAnnotateWith(bool $expected, Filter $rules, Pointcut\ServiceDefinition $def): void
	{
		$this->assertSame($expected, (bool) $def->match($rules));
	}

	/**
	 * @return array<int|string, array<int, bool|Rules>>
	 */
	public function dataMatchMethodAnnotateWith(): array
	{
		$data = [];
		$reader = new AnnotationReader();
		$data[] = [true, new Rules([new MethodAnnotateWithMatcher(Test::class, $reader)]), $this->createDefinition(Legie::class)];
		$data[] = [false, new Rules([new MethodAnnotateWithMatcher(Test::class, $reader)]), $this->createDefinition(SmegHead::class)];
		return $data;
	}
	/**
	 * @dataProvider dataMatchMethodAnnotateWith
	 */
	public function testMatchMethodAnnotateWith(bool $expected, Filter $rules, Pointcut\ServiceDefinition $def): void
	{
		$this->assertSame($expected, (bool) $def->match($rules));
	}

	public function testMatchesSetting(): void
	{
		$builder = new ContainerBuilder();
		$builder->parameters['foo']['dave'] = true;
		$builder->parameters['foo']['kryten'] = false;
		$builder->parameters['friendship'] = 'Is magic';
		$args = new SettingMatcher(Pointcut\Matcher\Criteria::create()->where('foo.dave', Pointcut\Matcher\Criteria::EQ, new PhpLiteral('TRUE')), $builder);
		$this->assertTrue($args->matches($this->mockMethod()));
		$args = new SettingMatcher(Pointcut\Matcher\Criteria::create()->where('foo.kryten', Pointcut\Matcher\Criteria::EQ, new PhpLiteral('FALSE')), $builder);
		$this->assertTrue($args->matches($this->mockMethod()));
		$args = new SettingMatcher(Pointcut\Matcher\Criteria::create()->where('friendship', Pointcut\Matcher\Criteria::EQ, new PhpLiteral("'Is magic'")), $builder);
		$this->assertTrue($args->matches($this->mockMethod()));
	}

	private function mockMethod(): Method
	{
		if (method_exists(ClassType::class, 'newInstanceWithoutConstructor')) {
			return ClassType::from(Method::class)->newInstanceWithoutConstructor();
		}

		return unserialize(sprintf('O:%d:"%s":0:{}', strlen(Method::class), Method::class));
	}

	private function createDefinition(string $class): Pointcut\ServiceDefinition
	{
		$def = new ServiceDefinition();
		$def->setType($class);
		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}
