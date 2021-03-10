<?php declare(strict_types = 1);

/**
 * Test: Contributte\Aop\Extension.
 *
 * @testCase Tests\Cases\ExtensionTest
 */

namespace Tests\Cases;

use Contributte\Aop\DI\AopExtension;
use Contributte\Aop\DI\AspectsExtension;
use Contributte\Aop\JoinPoint\AfterMethod;
use Contributte\Aop\JoinPoint\AfterReturning;
use Contributte\Aop\JoinPoint\AfterThrowing;
use Contributte\Aop\JoinPoint\AroundMethod;
use Contributte\Aop\JoinPoint\BeforeMethod;
use Contributte\Aop\JoinPoint\ExceptionAware;
use Contributte\Aop\JoinPoint\MethodInvocation;
use Contributte\Aop\JoinPoint\ResultAware;
use Nette;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\Cache\DI\CacheExtension;
use ReflectionException;
use RuntimeException;
use SystemContainer;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../files/aspect-examples.php';



class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return SystemContainer|Nette\DI\Container
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
		AspectsExtension::register($config);
		AopExtension::register($config);

		return $config->createContainer();
	}



	public function testAspectConfiguration()
	{
		$dic = $this->createContainer('aspect-configs');
		foreach ($services = array_keys($dic->findByTag(AspectsExtension::ASPECT_TAG)) as $serviceId) {
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
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		/** @var BeforeAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, BeforeAspect::class, 2, new BeforeMethod($service, 'magic', [3]));
	}



	public function testFunctionalConstructor()
	{
		$dic = $this->createContainer('constructor');
		$service = $dic->getByType(CommonService::class);
		self::assertAspectInvocation($service, ConstructorBeforeAspect::class, 0, new BeforeMethod($service, '__construct', [$dic]));
	}



	public function testFunctionalBefore_conditional()
	{
		$dic = $this->createContainer('before.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 0, new BeforeMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 1, null);
		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 2, null);
	}



	public function testFunctionalAround()
	{
		$dic = $this->createContainer('around');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, AroundAspect::class, 2, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAround_conditional()
	{
		$dic = $this->createContainer('around.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 0, new AroundMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 1, null);
		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 2, null);
	}



	public function testFunctionalAround_blocking()
	{
		$dic = $this->createContainer('around.blocking');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		$advice = self::assertAspectInvocation($service, AroundBlockingAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundBlockingAspect $advice */

		$service->return = 3;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 1, new AroundMethod($service, 'magic', [2]));

		$service->throw = true;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 2, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 3, new AroundMethod($service, 'magic', [3]));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 4, new AroundMethod($service, 'magic', [3]));

		$advice->modifyThrow = true;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', 'Everybody is dead Dave.');
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 5, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAfterReturning()
	{
		$dic = $this->createContainer('afterReturning');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		/** @var AfterReturningAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, AfterReturningAspect::class, 1, new AfterReturning($service, 'magic', [2], 6));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::same([2], $service->calls[2]);
		self::assertAspectInvocation($service, AfterReturningAspect::class, 2, new AfterReturning($service, 'magic', [2], 9));
	}



	public function testFunctionalAfterReturning_conditional()
	{
		$dic = $this->createContainer('afterReturning.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));

		$service->return = 3;
		Assert::same(3, $service->magic(1));

		$service->return = 2;
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [0], 0));
		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 1, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 2, null);
	}



	public function testFunctionalAfterThrowing()
	{
		$dic = $this->createContainer('afterThrowing');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$service->throw = true;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [2], new RuntimeException("Something's fucky")));
	}



	public function testFunctionalAfter()
	{
		$dic = $this->createContainer('after');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [2], null, new RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll()
	{
		$dic = $this->createContainer('all');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll_doubled()
	{
		$dic = $this->createContainer('all.doubled');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, SecondBeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, SecondAroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, SecondAfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, SecondAfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, SecondBeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, SecondAroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, SecondAfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, SecondAfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
	}



	/**
	 * @param object $service
	 * @param string $adviceClass
	 * @param int $adviceCallIndex
	 * @param MethodInvocation $joinPoint
	 * @return object
	 */
	private static function assertAspectInvocation($service, $adviceClass, $adviceCallIndex, ?MethodInvocation $joinPoint = null)
	{
		$advices = array_filter(self::getAspects($service), function ($advice) use ($adviceClass) {
			return get_class($advice) === $adviceClass;
		});
		Assert::true(!empty($advices));
		$advice = reset($advices);
		Assert::true($advice instanceof $adviceClass);

		if ($joinPoint === null) {
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

		if ($joinPoint instanceof ResultAware) {
			/** @var AfterReturning $call */
			Assert::same($joinPoint->getResult(), $call->getResult());
		}

		if ($joinPoint instanceof ExceptionAware) {
			/** @var AfterThrowing $call */
			Assert::equal($joinPoint->getException() ? get_class($joinPoint->getException()) : null, $call->getException() ? get_class($call->getException()) : null);
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

			$propRefl->setAccessible(true);
			return $propRefl->getValue($service);

		} catch (ReflectionException $e) {
			return [];
		}
	}

}

(new ExtensionTest())->run();
