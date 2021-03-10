<?php declare(strict_types = 1);

/**
 * Test: Contributte\Aop\PointcutRules.
 *
 * @testCase Tests\Cases\PointcutRulesTest
 */

namespace Tests\Cases;

use Contributte\Aop\Pointcut;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/pointcut-examples.php';



class ServiceDefinitionTest extends Tester\TestCase
{

	public function testInheritedConstructor()
	{
		$definition = $this->createDefinition(InheritedClass::class);
		Assert::equal($definition->getOpenMethods(), ['__construct' => new Pointcut\Method(Nette\Reflection\Method::from(InheritedClass::class, '__construct'), $definition)]);
	}


	private function createDefinition(string $type): Pointcut\ServiceDefinition
	{
		$def = new Nette\DI\ServiceDefinition();
		$def->setType($type);

		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}

(new ServiceDefinitionTest())->run();
