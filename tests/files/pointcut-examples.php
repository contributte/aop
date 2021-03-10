<?php

namespace Tests\Cases;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Doctrine\Common\Annotations\Annotation;
use Nette;

interface Rimmer
{

}



interface Lister
{

}



interface Kryten
{

}



interface Cat
{

}



class Legie implements Rimmer, Lister, Kryten, Cat
{

	/**
	 * @Test()
	 */
	public function publicCalculation()
	{
	}



	protected function protectedCalculation()
	{
	}



	private function privateCalculation()
	{
	}



	public function injectBar()
	{
	}

}



/**
 * @Test()
 */
class SmegHead
{

	public function injectFoo()
	{
	}



	public function bar()
	{
	}

}



/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Test extends Annotation
{

}



class CustomTemplate implements Nette\Application\UI\ITemplate
{

	public function render(): void
	{
	}

	public function setFile(string $file)
	{
	}

	public function getFile(): ?string
	{
	}

}



class MyPointcutFilter implements Filter
{

	public function matches(Method $method): bool
	{
		return $method->getClassName() === Legie::class;
	}



	public function listAcceptedTypes(): array
	{
		return [];
	}

}

interface LoggerInterface
{

}

class CommonClass
{

}

class PackageClass
{

}

class PointcutTestingAspect
{

}

class FeedAggregator
{

}

class MockPresenter extends Nette\Application\UI\Presenter
{

	public function renderDefault()
	{
	}

	public function actionDefault()
	{
	}

	public function handleSort()
	{
	}

}

class BaseClass
{

	public function __construct($x)
	{
	}

}

class InheritedClass extends BaseClass
{

	public function __construct($x, $y)
	{
		parent::__construct($x);
	}

}
