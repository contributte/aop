<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Aop\PhpGenerator\AdvisedClassType;
use Nette\PhpGenerator\ClassType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Tests\Files\TestClass;

class AdvisedClassTypeTest extends TestCase
{

	public function testSetMethodInstance()
	{
		$testClass = ClassType::from(TestClass::class);
		$method = AdvisedClassType::setMethodInstance($testClass, $testClass->getMethod('first'));
		Assert::assertCount(2, $method->getParameters());
	}

}
