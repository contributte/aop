<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Aop\Pointcut\Method;
use Contributte\Aop\Pointcut\ServiceDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Files\Pointcut\InheritedClass;

class ServiceDefinitionTest extends TestCase
{

	public function testInheritedConstructor(): void
	{
		$definition = $this->createDefinition(InheritedClass::class);
		$this->assertEquals($definition->getOpenMethods(), ['__construct' => new Method(\Nette\Reflection\Method::from(InheritedClass::class, '__construct'), $definition)]);
	}

	private function createDefinition(string $type): ServiceDefinition
	{
		$def = new \Nette\DI\Definitions\ServiceDefinition();
		$def->setType($type);
		return new ServiceDefinition($def, 'abc');
	}

}
