<?php

namespace Tests\Cases;

use Contributte\Aop\PhpGenerator\AdvisedClassType;
use Nette\PhpGenerator\ClassType;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



class AdvisedClassTypeTest extends Tester\TestCase
{

	public function testSetMethodInstance()
	{
		$testClass = ClassType::from(TestClass::class);

		$method = AdvisedClassType::setMethodInstance($testClass, $testClass->getMethod('first'));
		$string = $methodCode = $method->__toString();
		Assert::count(2, $method->getParameters());
	}

}

class TestClass
{

	public function first(int $param, string $second)
	{
	}

}
(new AdvisedClassTypeTest())->run();
