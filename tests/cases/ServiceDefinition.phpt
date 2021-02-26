<?php

/**
 * Test: Contributte\Aop\PointcutRules.
 *
 * @testCase Tests\Cases\PointcutRulesTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Contributte\Aop
 */

namespace Tests\Cases;

use Doctrine\Common\Annotations\AnnotationReader;

use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\Matcher;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Contributte\Aop\Pointcut\Matcher\SettingMatcher;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/pointcut-examples.php';



/**
 * @author Karel Hak <karel.hak@fregis.cz>
 */
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
