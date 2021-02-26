<?php

/**
 * Test: Contributte\Aop\Extension.
 *
 * @testCase Tests\Cases\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Contributte\Aop
 */

namespace Tests\Cases;


use Contributte\Aop\JoinPoint\AfterMethod;
use Contributte\Aop\JoinPoint\AfterReturning;
use Contributte\Aop\JoinPoint\AfterThrowing;
use Contributte\Aop\JoinPoint\AroundMethod;
use Contributte\Aop\JoinPoint\BeforeMethod;
use Contributte\Aop\JoinPoint\MethodInvocation;
use Nette;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\Cache\DI\CacheExtension;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/aspect-examples.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/../config/' . $configFile . '.neon');

		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('nettrine.cache', new CacheExtension());
		};
		\Contributte\Aop\DI\AspectsExtension::register($config);
		\Contributte\Aop\DI\AopExtension::register($config);

		$container = $config->createContainer();
		return $container;
	}



	public function testAspectConfiguration()
	{
		$dic = $this->createContainer('aspect-configs');
		foreach ($services = array_keys($dic->findByTag(\Contributte\Aop\DI\AspectsExtension::ASPECT_TAG)) as $serviceId) {
			$service = $dic->getService($serviceId);
			Assert::true($service instanceof AspectWithArguments);
			Assert::same([$dic->getByType(Nette\Http\Request::class)], $service->args);
		}
		Assert::same(4, count($services));
	}



	public function testIfAspectAppliedOnCreatedObject()
	{
		$dic = $this->createContainer('factory');

		$service = $dic->getByType(CommonService::class);
		$createdObject = $dic->getByType(ICommonServiceFactory::class)->create();
		Assert::notEqual(CommonService::class, get_class($service));
		Assert::notEqual(CommonService::class, get_class($createdObject));
	}



	public function testFunctionalBefore()
	{
		$dic = $this->createContainer('before');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		/** @var BeforeAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 1, new BeforeMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 2, new BeforeMethod($service, 'magic', [3]));
	}



	public function testFunctionalConstructor()
	{
		$dic = $this->createContainer('constructor');
		$service = $dic->getByType('Tests\Cases\CommonService');
		self::assertAspectInvocation($service, 'Tests\Cases\ConstructorBeforeAspect', 0, new BeforeMethod($service, '__construct', [$dic]));
	}



	public function testFunctionalBefore_conditional()
	{
		$dic = $this->createContainer('before.conditional');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalBeforeAspect', 0, new BeforeMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalBeforeAspect', 1, NULL);
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalBeforeAspect', 2, NULL);
	}



	public function testFunctionalAround()
	{
		$dic = $this->createContainer('around');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 1, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 2, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAround_conditional()
	{
		$dic = $this->createContainer('around.conditional');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAroundAspect', 0, new AroundMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAroundAspect', 1, NULL);
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAroundAspect', 2, NULL);
	}



	public function testFunctionalAround_blocking()
	{
		$dic = $this->createContainer('around.blocking');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		$advice = self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundBlockingAspect $advice */

		$service->return = 3;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 1, new AroundMethod($service, 'magic', [2]));

		$service->throw = TRUE;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 2, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 3, new AroundMethod($service, 'magic', [3]));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 4, new AroundMethod($service, 'magic', [3]));

		$advice->modifyThrow = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Everybody is dead Dave.");
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundBlockingAspect', 5, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAfterReturning()
	{
		$dic = $this->createContainer('afterReturning');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'Tests\Cases\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		/** @var AfterReturningAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\AfterReturningAspect', 1, new AfterReturning($service, 'magic', [2], 6));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::same([2], $service->calls[2]);
		self::assertAspectInvocation($service, 'Tests\Cases\AfterReturningAspect', 2, new AfterReturning($service, 'magic', [2], 9));
	}



	public function testFunctionalAfterReturning_conditional()
	{
		$dic = $this->createContainer('afterReturning.conditional');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));

		$service->return = 3;
		Assert::same(3, $service->magic(1));

		$service->return = 2;
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAfterReturningAspect', 0, new AfterReturning($service, 'magic', [0], 0));
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAfterReturningAspect', 1, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'Tests\Cases\ConditionalAfterReturningAspect', 2, NULL);
	}



	public function testFunctionalAfterThrowing()
	{
		$dic = $this->createContainer('afterThrowing');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'Tests\Cases\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [2], new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAfter()
	{
		$dic = $this->createContainer('after');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 1, new AfterMethod($service, 'magic', [2], NULL, new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll()
	{
		$dic = $this->createContainer('all');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll_doubled()
	{
		$dic = $this->createContainer('all.doubled');
		$service = $dic->getByType('Tests\Cases\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondBeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, 'Tests\Cases\BeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondBeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\AroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'Tests\Cases\AfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'Tests\Cases\SecondAfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
	}



	/**
	 * @param object $service
	 * @param string $adviceClass
	 * @param int $adviceCallIndex
	 * @param MethodInvocation $joinPoint
	 * @return object
	 */
	private static function assertAspectInvocation($service, $adviceClass, $adviceCallIndex, MethodInvocation $joinPoint = NULL)
	{
		$advices = array_filter(self::getAspects($service), function ($advice) use ($adviceClass) {
			return get_class($advice) === $adviceClass;
		});
		Assert::true(!empty($advices));
		$advice = reset($advices);
		Assert::true($advice instanceof $adviceClass);

		if ($joinPoint === NULL) {
			Assert::true(empty($advice->calls[$adviceCallIndex]));

			return $advice;
		}

		Assert::true(!empty($advice->calls[$adviceCallIndex]));
		$call = $advice->calls[$adviceCallIndex];
		/** @var MethodInvocation $call */

		$joinPointClass = get_class($joinPoint);
		Assert::true($call instanceof $joinPointClass);
		Assert::equal($joinPoint->getArguments(), $call->getArguments());
		Assert::same($joinPoint->getTargetObject(), $call->getTargetObject());
		Assert::same($joinPoint->getTargetReflection()->getName(), $call->getTargetReflection()->getName());

		if ($joinPoint instanceof \Contributte\Aop\JoinPoint\ResultAware) {
			/** @var AfterReturning $call */
			Assert::same($joinPoint->getResult(), $call->getResult());
		}

		if ($joinPoint instanceof \Contributte\Aop\JoinPoint\ExceptionAware) {
			/** @var AfterThrowing $call */
			Assert::equal($joinPoint->getException() ? get_class($joinPoint->getException()) : NULL, $call->getException() ? get_class($call->getException()) : NULL);
			Assert::equal($joinPoint->getException() ? $joinPoint->getException()->getMessage() : '', $call->getException() ? $call->getException()->getMessage() : '');
		}

		return $advice;
	}



	/**
	 * @param string $service
	 * @return array
	 */
	private static function getAspects($service)
	{
		try {
			$propRefl = (Nette\Reflection\ClassType::from($service))
				->getProperty('_contributte_aopAdvices'); // internal property

			$propRefl->setAccessible(TRUE);
			return $propRefl->getValue($service);

		} catch (\ReflectionException $e) {
			return [];
		}
	}

}

(new ExtensionTest())->run();
