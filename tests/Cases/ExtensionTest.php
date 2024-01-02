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
use Contributte\Tester\Utils\Neonkit;
use Nette;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Tests\Files\Aspects\AfterAspect;
use Tests\Files\Aspects\AfterReturningAspect;
use Tests\Files\Aspects\AfterThrowingAspect;
use Tests\Files\Aspects\AroundAspect;
use Tests\Files\Aspects\AroundBlockingAspect;
use Tests\Files\Aspects\AspectWithArguments;
use Tests\Files\Aspects\BeforeAspect;
use Tests\Files\Aspects\CommonService;
use Tests\Files\Aspects\ConditionalAfterReturningAspect;
use Tests\Files\Aspects\ConditionalAroundAspect;
use Tests\Files\Aspects\ConditionalBeforeAspect;
use Tests\Files\Aspects\ConstructorBeforeAspect;
use Tests\Files\Aspects\ICommonServiceFactory;
use Tests\Files\Aspects\SecondAfterAspect;
use Tests\Files\Aspects\SecondAfterReturningAspect;
use Tests\Files\Aspects\SecondAfterThrowingAspect;
use Tests\Files\Aspects\SecondAroundAspect;
use Tests\Files\Aspects\SecondBeforeAspect;
use Throwable;

class ExtensionTest extends TestCase
{

	public function createContainer(string $configFile): Nette\DI\Container
	{
		$config = new Nette\Configurator();
		$tmpDir = __DIR__ . '/../tmp/' . uniqid();
		$config->setTempDirectory($tmpDir);
		$config->addConfig(__DIR__ . '/../config/' . $configFile . '.neon');

		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler): void {
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				php:
					date.timezone: Europe/Prague

				services:
					cacheStorage:
						class: Nette\Caching\Storages\MemoryStorage
				http:
					frames: null

				session:
					autoStart: false
			NEON
			));
		};

		AspectsExtension::register($config);
		AopExtension::register($config);

		return $config->createContainer();
	}


	public function testAspectConfiguration(): void
	{
		$dic = $this->createContainer('aspect-configs');
		foreach ($services = array_keys($dic->findByTag(AspectsExtension::ASPECT_TAG)) as $serviceId) {
			$service = $dic->getService($serviceId);
			$this->assertInstanceOf(AspectWithArguments::class, $service);
			$this->assertSame([$dic->getByType(Nette\Http\Request::class)], $service->args);
		}

		$this->assertCount(4, $services);
	}


	public function testIfAspectAppliedOnCreatedObject(): void
	{
		$dic = $this->createContainer('factory');

		$service = $dic->getByType(CommonService::class);
		$createdObject = $dic->getByType(ICommonServiceFactory::class)->create();
		$this->assertNotEquals(CommonService::class, get_class($service));
		$this->assertNotEquals(CommonService::class, get_class($createdObject));
	}


	public function testFunctionalBefore(): void
	{
		$dic = $this->createContainer('before');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		/** @var BeforeAspect $advice */

		$service->return = 3;
		$this->assertSame(6, $service->magic(2));
		$this->assertSame([2], $service->calls[1]);
		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		$this->assertSame(9, $service->magic(2));
		$this->assertSame([3], $service->calls[2]);
		self::assertAspectInvocation($service, BeforeAspect::class, 2, new BeforeMethod($service, 'magic', [3]));
	}


	public function testFunctionalConstructor(): void
	{
		$dic = $this->createContainer('constructor');
		$service = $dic->getByType(CommonService::class);
		self::assertAspectInvocation($service, ConstructorBeforeAspect::class, 0, new BeforeMethod($service, '__construct', [$dic]));
	}


	public function testFunctionalBefore_conditional(): void
	{
		$dic = $this->createContainer('before.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(0, $service->magic(0));
		$this->assertSame(2, $service->magic(1));
		$this->assertSame(4, $service->magic(2));

		$this->assertSame([0], $service->calls[0]);
		$this->assertSame([1], $service->calls[1]);
		$this->assertSame([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 0, new BeforeMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 1, null);
		self::assertAspectInvocation($service, ConditionalBeforeAspect::class, 2, null);
	}

	public function testFunctionalAround(): void
	{
		$dic = $this->createContainer('around');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundAspect $advice */

		$service->return = 3;
		$this->assertSame(6, $service->magic(2));
		$this->assertSame([2], $service->calls[1]);
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		$this->assertSame(9, $service->magic(2));
		$this->assertSame([3], $service->calls[2]);
		self::assertAspectInvocation($service, AroundAspect::class, 2, new AroundMethod($service, 'magic', [3]));
	}


	public function testFunctionalAround_conditional(): void
	{
		$dic = $this->createContainer('around.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(0, $service->magic(0));
		$this->assertSame(2, $service->magic(1));
		$this->assertSame(4, $service->magic(2));

		$this->assertSame([0], $service->calls[0]);
		$this->assertSame([1], $service->calls[1]);
		$this->assertSame([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 0, new AroundMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 1, null);
		self::assertAspectInvocation($service, ConditionalAroundAspect::class, 2, null);
	}


	public function testFunctionalAround_blocking(): void
	{
		$dic = $this->createContainer('around.blocking');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertNull($service->magic(2));
		$this->assertEmpty($service->calls);
		$advice = self::assertAspectInvocation($service, AroundBlockingAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundBlockingAspect $advice */

		$service->return = 3;
		$this->assertNull($service->magic(2));
		$this->assertEmpty($service->calls);
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 1, new AroundMethod($service, 'magic', [2]));

		$service->throw = true;
		$this->assertNull($service->magic(2));
		$this->assertEmpty($service->calls);
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 2, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		$this->assertNull($service->magic(2));
		$this->assertEmpty($service->calls);
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 3, new AroundMethod($service, 'magic', [3]));

		$advice->modifyReturn = 9;
		$this->assertSame(9, $service->magic(2));
		$this->assertEmpty($service->calls);
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 4, new AroundMethod($service, 'magic', [3]));

		$advice->modifyThrow = true;

		try {
			$service->magic(2);
		} catch (Throwable $e) {
			$this->assertEquals('Everybody is dead Dave.', $e->getMessage());
		}

		$this->assertEmpty($service->calls);
		self::assertAspectInvocation($service, AroundBlockingAspect::class, 5, new AroundMethod($service, 'magic', [3]));
	}


	public function testFunctionalAfterReturning(): void
	{
		$dic = $this->createContainer('afterReturning');

		/** @var CommonService $service */
		$service = $dic->getByType(CommonService::class);

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		/** @var AfterReturningAspect $advice */

		$service->return = 3;
		$this->assertSame(6, $service->magic(2));
		$this->assertSame([2], $service->calls[1]);
		self::assertAspectInvocation($service, AfterReturningAspect::class, 1, new AfterReturning($service, 'magic', [2], 6));

		$advice->modifyReturn = 9;
		$this->assertSame(9, $service->magic(2));
		$this->assertSame([2], $service->calls[2]);
		self::assertAspectInvocation($service, AfterReturningAspect::class, 2, new AfterReturning($service, 'magic', [2], 9));
	}


	public function testFunctionalAfterReturning_conditional(): void
	{
		$dic = $this->createContainer('afterReturning.conditional');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(0, $service->magic(0));

		$service->return = 3;
		$this->assertSame(3, $service->magic(1));

		$service->return = 2;
		$this->assertSame(4, $service->magic(2));

		$this->assertSame([0], $service->calls[0]);
		$this->assertSame([1], $service->calls[1]);
		$this->assertSame([2], $service->calls[2]);

		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [0], 0));
		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 1, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, ConditionalAfterReturningAspect::class, 2, null);
	}


	public function testFunctionalAfterThrowing(): void
	{
		$dic = $this->createContainer('afterThrowing');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$service->throw = true;
		try {
			$service->magic(2);
		} catch (Throwable $e) {
			$this->assertEquals("Something's fucky", $e->getMessage());
		}

		$this->assertSame([2], $service->calls[0]);
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [2], new RuntimeException("Something's fucky")));
	}


	public function testFunctionalAfter(): void
	{
		$dic = $this->createContainer('after');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;

		try {
			$service->magic(2);
		} catch (Throwable $e) {
			$this->assertEquals("Something's fucky", $e->getMessage());
		}

		$this->assertSame([2], $service->calls[1]);
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [2], null, new RuntimeException("Something's fucky")));
	}


	public function testFunctionalAll(): void
	{
		$dic = $this->createContainer('all');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;
		try {
			$service->magic(3);
		} catch (Throwable $e) {
			$this->assertEquals("Something's fucky", $e->getMessage());
		}

		$this->assertSame([3], $service->calls[1]);
		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
	}


	public function testFunctionalAll_doubled(): void
	{
		$dic = $this->createContainer('all.doubled');
		$service = $dic->getByType(CommonService::class);
		/** @var CommonService $service */

		$this->assertSame(4, $service->magic(2));
		$this->assertSame([2], $service->calls[0]);
		self::assertAspectInvocation($service, BeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, SecondBeforeAspect::class, 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, SecondAroundAspect::class, 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, AfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, SecondAfterReturningAspect::class, 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, AfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, SecondAfterAspect::class, 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = true;
		try {
			$service->magic(3);
		} catch (Throwable $e) {
			$this->assertEquals("Something's fucky", $e->getMessage());
		}

		$this->assertSame([3], $service->calls[1]);

		self::assertAspectInvocation($service, BeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, SecondBeforeAspect::class, 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, SecondAroundAspect::class, 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, AfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, SecondAfterThrowingAspect::class, 0, new AfterThrowing($service, 'magic', [3], new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, AfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, SecondAfterAspect::class, 1, new AfterMethod($service, 'magic', [3], null, new RuntimeException("Something's fucky")));
	}


	private static function assertAspectInvocation(object $service, string $adviceClass, int $adviceCallIndex, ?MethodInvocation $joinPoint = null): object
	{
		$advices = array_filter(self::getAspects($service), function ($advice) use ($adviceClass) {
			return get_class($advice) === $adviceClass;
		});

		self::assertNotEmpty($advices);
		$advice = reset($advices);
		self::assertInstanceOf($adviceClass, $advice);

		if ($joinPoint === null) {
			self::assertArrayNotHasKey($adviceCallIndex, $advice->calls);

			return $advice;
		}

		self::assertNotEmpty($advice->calls[$adviceCallIndex]);
		$call = $advice->calls[$adviceCallIndex];
		/** @var MethodInvocation $call */

		$joinPointClass = get_class($joinPoint);
		self::assertInstanceOf($joinPointClass, $call);
		self::assertEquals($joinPoint->getArguments(), $call->getArguments());
		self::assertSame($joinPoint->getTargetObject(), $call->getTargetObject());
		self::assertSame($joinPoint->getTargetReflection()->getName(), $call->getTargetReflection()->getName());

		if ($joinPoint instanceof ResultAware) {
			/** @var AfterReturning $call */
			self::assertSame($joinPoint->getResult(), $call->getResult());
		}

		if ($joinPoint instanceof ExceptionAware) {
			/** @var AfterThrowing $call */
			self::assertEquals($joinPoint->getException() ? get_class($joinPoint->getException()) : null, $call->getException() ? get_class($call->getException()) : null);
			self::assertEquals($joinPoint->getException() ? $joinPoint->getException()->getMessage() : '', $call->getException() ? $call->getException()->getMessage() : '');
		}

		return $advice;
	}


	/**
	 * @param string|object $service
	 * @return object[]
	 */
	private static function getAspects($service): array
	{
		try {
			$propRefl = new ReflectionProperty($service, '_contributte_aopAdvices');

			$propRefl->setAccessible(true);
			return $propRefl->getValue($service);

		} catch (ReflectionException $e) {
			return [];
		}
	}

}
