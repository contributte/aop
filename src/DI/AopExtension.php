<?php declare(strict_types = 1);

namespace Contributte\Aop\DI;

use Contributte\Aop\PhpGenerator\AdvisedClassType;
use Contributte\Aop\Pointcut;
use Nette;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator as Code;
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AopExtension extends Nette\DI\CompilerExtension
{

	/** @var array<string, string[]>|null */
	private ?array $classes = [];

	/** @var Pointcut\ServiceDefinition[] */
	private array $serviceDefinitions = [];

	private ?string $compiledFile;

	public static function register(Nette\Configurator $configurator): void
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('aop', new AopExtension());
		};
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$this->compiledFile = null;

		$namespace = 'Container_' . substr(md5(serialize([
			$builder->parameters,
			$this->compiler->exportDependencies(),
			PHP_VERSION_ID - PHP_RELEASE_VERSION,
		])), 0, 10);

		$file = new Code\PhpFile();
		$cg = $file->addNamespace('Contributte\\Aop_CG\\' . $namespace);
		$cg->addUse(Pointcut\Matcher\Criteria::class);
		$cg->addUse(PropertyAccess::class);

		foreach ($this->findAdvisedMethods() as $serviceId => $pointcuts) {
			$service = $this->getWrappedDefinition($serviceId);
			$advisedClass = AdvisedClassType::fromServiceDefinition($service, $cg);
			$constructorInject = false;

			foreach ($pointcuts as $methodAdvices) {
				/** @var AdviceDefinition $methodAdvice */
				$methodAdvice = reset($methodAdvices);
				/** @var Pointcut\Method $targetMethod */
				$targetMethod = $methodAdvice->getTargetMethod();

				$newMethod = $targetMethod->getPointcutCode();
				AdvisedClassType::setMethodInstance($advisedClass, $newMethod->getMethod());
				AdvisedClassType::generatePublicProxyMethod($advisedClass, $targetMethod->getCode()->getMethod());
				$constructorInject = $constructorInject || strtolower($newMethod->getName()) === '__construct';

				foreach ($methodAdvices as $adviceDef) {
					$newMethod->addAdvice($adviceDef);
				}

				$newMethod->beforePrint();
			}

			$this->patchService($serviceId, $advisedClass, $cg, $constructorInject);
		}

		if (!$cg->getClasses()) {
			return;
		}

		require_once $this->compiledFile = $this->writeGeneratedCode($file, $cg);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		if (!$this->compiledFile) {
			return;
		}

		$init = $class->getMethods()['initialize'];
		$init->addBody('require_once ?;', [$this->compiledFile]);
	}

	private function patchService(string $serviceId, Code\ClassType $advisedClass, Code\PhpNamespace $cg, bool $constructorInject = false): void
	{
		static $publicSetup;
		if ($publicSetup === null) {
			$refl = new ReflectionProperty(ServiceDefinition::class, 'setup');
			$publicSetup = $refl->isPublic();
		}

		/** @var ServiceDefinition|FactoryDefinition $def */
		$def = $this->getContainerBuilder()->getDefinition($serviceId);
		if ($def instanceof FactoryDefinition) {
			$def = $def->getResultDefinition();
		}

		$factory = $def->getFactory();
		$def->setFactory(new Statement($cg->getName() . '\\' . $advisedClass->getName(), $factory->arguments));

		if (!$constructorInject) {
			$statement = new Statement(AdvisedClassType::CG_INJECT_METHOD, ['@Nette\DI\Container']);

			if ($publicSetup) {
				array_unshift($def->setup, $statement);

			} else {
				$setup = $def->getSetup();
				array_unshift($setup, $statement);
				$def->setSetup($setup);
			}
		}
	}

	private function writeGeneratedCode(Code\PhpFile $file, Code\PhpNamespace $namespace): string
	{
		$builder = $this->getContainerBuilder();

		if (!is_dir($tempDir = Nette\DI\Helpers::expand('%tempDir%/cache/_Contributte.Aop', $builder->parameters))) {
			mkdir($tempDir, 0777, true);
		}

		$key = md5(serialize($builder->parameters) . serialize(array_keys($namespace->getClasses())));

		$cached = $tempDir . '/' . $key . '.php';

		file_put_contents($cached, (string) $file);

		return $cached;
	}

	/**
	 * @return array<string, array<string, AdviceDefinition[]>>
	 */
	private function findAdvisedMethods(): array
	{
		$builder = $this->getContainerBuilder();
		$builder->resolve();

		$matcherFactory = new Pointcut\MatcherFactory($builder);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory));

		$advisedMethods = [];
		$this->classes = null;

		foreach ($builder->findByTag(AspectsExtension::ASPECT_TAG) as $aspectId => $meta) {
			$advices = $analyzer->analyze($aspectService = $this->getWrappedDefinition($aspectId));

			/** @var Pointcut\Filter[] $filters */
			foreach ($advices as $advice => $filters) {
				foreach ($filters as $adviceType => $filter) {
					$types = $filter->listAcceptedTypes();
					$services = $types !== [] ? $this->findByTypes($types) : array_keys($builder->getDefinitions());

					foreach ($services as $serviceId) {
						foreach ($this->getWrappedDefinition($serviceId)->match($filter) as $method) {
							$advisedMethods[$serviceId][$method->getName()][] = new AdviceDefinition($adviceType, $method, $aspectService->openMethods[$advice], $filter);
						}
					}
				}
			}
		}

		return $advisedMethods;
	}

	/**
	 * @param string[] $types
	 * @return array<string, string>
	 */
	private function findByTypes(array $types): array
	{
		if ($this->classes === null) {
			$this->classes = [];
			foreach ($this->getContainerBuilder()->getDefinitions() as $name => $def) {
				if ($def->getType() !== null) {
					$additional = [];
					if ($def instanceof FactoryDefinition) {
						$this->classes[strtolower((string) $def->getResultDefinition()->getType())][] = (string) $name;
					}

					$defParents = class_parents($def->getType()) ?: [];
					$defImplements = class_implements($def->getType()) ?: [];

					foreach ($defParents + $defImplements + [$def->getType()] + $additional as $parent) {
						$this->classes[strtolower($parent)][] = (string) $name;
					}
				}
			}
		}

		$services = [];
		foreach (array_filter((array) $types) as $type) {
			$lower = ltrim(strtolower($type), '\\');
			if (isset($this->classes[$lower])) {
				$services = array_merge($services, $this->classes[$lower]);
			}
		}

		return array_unique($services);
	}

	private function getWrappedDefinition(string $id): Pointcut\ServiceDefinition
	{
		if (!isset($this->serviceDefinitions[$id])) {
			$def = $this->getContainerBuilder()->getDefinition($id);
			if ($def instanceof FactoryDefinition) {
				$def = $def->getResultDefinition();
			}

			$this->serviceDefinitions[$id] = new Pointcut\ServiceDefinition($def, $id);
		}

		return $this->serviceDefinitions[$id];
	}

}
