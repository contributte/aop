<?php declare(strict_types = 1);

namespace Contributte\Aop\PhpGenerator;

use Contributte\Aop\Attributes\After;
use Contributte\Aop\Attributes\AfterReturning;
use Contributte\Aop\Attributes\AfterThrowing;
use Contributte\Aop\Attributes\Around;
use Contributte\Aop\Attributes\Before;
use Contributte\Aop\DI\AdviceDefinition;
use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Exceptions\NotImplementedException;
use Contributte\Aop\Pointcut\RuntimeFilter;
use Nette;
use Nette\PhpGenerator as Code;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @method string getName()
 */
class PointcutMethod
{

	/** @var string[] */
	private array $before = [];

	/** @var string[] */
	private array $around = [];

	/** @var string[] */
	private array $afterReturning = [];

	/** @var string[] */
	private array $afterThrowing = [];

	/** @var string[] */
	private array $after = [];

	private Code\Method $method;

	private Code\Dumper $dumper;

	public function __construct(ReflectionMethod $from)
	{
		$this->method = (new Code\Factory())->fromMethodReflection($from);
		$this->dumper = new Code\Dumper();
	}

	public static function from(ReflectionMethod $from): PointcutMethod
	{
		$method = new self($from);
		$params = [];
		$factory = new Code\Factory();
		foreach ($from->getParameters() as $param) {
			$params[$param->getName()] = $factory->fromParameterReflection($param);
		}

		$method->method->setParameters($params);
		if ($from instanceof ReflectionMethod) {
			$isInterface = $from->getDeclaringClass()->isInterface();
			$method->method->setStatic($from->isStatic());
			$method->method->setVisibility($from->isPrivate() ? 'private' : ($from->isProtected() ? 'protected' : ($isInterface ? null : 'public')));
			$method->method->setFinal($from->isFinal());
			$method->method->setAbstract($from->isAbstract() && !$isInterface);
			$method->method->setBody($from->isAbstract() ? null : '');
		}

		$method->method->setReturnReference($from->returnsReference());
		$method->method->setVariadic($from->isVariadic());
		$docComment = $from->getDocComment();
		if ($docComment !== false) {
			$method->method->setComment(Code\Helpers::unformatDocComment($docComment));
		}

		if ($from->hasReturnType()) {
			/** @var ReflectionNamedType $returnType */
			$returnType = $from->getReturnType();
			$method->method->setReturnType($returnType->getName());
			$method->method->setReturnNullable($returnType->allowsNull());
		}

		return $method;
	}

	public function addAdvice(AdviceDefinition $adviceDef): void
	{
		$adviceMethod = $adviceDef->getAdvice();

		switch ($adviceDef->getAdviceType()) {
			case Before::getClassName():
				$this->before[] = $this->generateRuntimeCondition($adviceDef, $this->dumper->format(
					'$this->__getAdvice(?)->?($__before = new \Contributte\Aop\JoinPoint\BeforeMethod($this, __FUNCTION__, $__arguments));' . "\n" .
					'$__arguments = $__before->getArguments();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));

				break;

			case Around::getClassName():
				$this->around[] = $this->generateRuntimeCondition($adviceDef, $this->dumper->format(
					'$__around->addChainLink($this->__getAdvice(?), ?);',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case AfterReturning::getClassName():
				$this->afterReturning[] = $this->generateRuntimeCondition($adviceDef, $this->dumper->format(
					'$this->__getAdvice(?)->?($__afterReturning = new \Contributte\Aop\JoinPoint\AfterReturning($this, __FUNCTION__, $__arguments, $__result));' . "\n" .
					'$__result = $__afterReturning->getResult();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case AfterThrowing::getClassName():
				$this->afterThrowing[] = $this->generateRuntimeCondition($adviceDef, $this->dumper->format(
					'$this->__getAdvice(?)->?(new \Contributte\Aop\JoinPoint\AfterThrowing($this, __FUNCTION__, $__arguments, $__exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case After::getClassName():
				$this->after[] = $this->generateRuntimeCondition($adviceDef, $this->dumper->format(
					'$this->__getAdvice(?)->?(new \Contributte\Aop\JoinPoint\AfterMethod($this, __FUNCTION__, $__arguments, $__result, $__exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			default:
				throw new InvalidArgumentException('Unknown advice type ' . $adviceDef->getAdviceType());
		}
	}


	public function getMethod(): Code\Method
	{
		return $this->method;
	}


	private function generateRuntimeCondition(AdviceDefinition $adviceDef, string $code): string
	{
		$filter = $adviceDef->getFilter();
		if (!$filter instanceof RuntimeFilter) {
			return $code;

		}

		if (!$condition = $filter->createCondition()) {
			return $code;
		}

		foreach ($adviceDef->getTargetMethod()->getParameterNames() as $i => $name) {
			$condition = str_replace('$' . $name, '$__arguments[' . $i . ']', (string) $condition);
		}

		return $this->dumper->format("if ? {\n?\n}", new Code\PhpLiteral((string) $condition), new Code\PhpLiteral(Nette\Utils\Strings::indent($code)));
	}



	public function beforePrint(): void
	{
		$this->method->setBody('');

		if (strtolower($this->method->getName()) === '__construct') {
			$this->method->addParameter('_contributte_aopContainer')
				->setType(Nette\DI\Container::class);
			$this->method->addBody('$this->_contributte_aopContainer = $_contributte_aopContainer;');
		}

		$this->method->addBody('$__arguments = func_get_args(); $__exception = $__result = NULL;');

		if ($this->before) {
			foreach ($this->before as $before) {
				$this->method->addBody($before);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->method->addBody('try {');
		}

		if (!$this->around) {
			$parentCall = $this->dumper->format('$__result = call_user_func_array([parent::class, ?], $__arguments);', $this->method->getName());
		} else {
			$parentCall = $this->dumper->format('$__around = new \Contributte\Aop\JoinPoint\AroundMethod($this, __FUNCTION__, $__arguments);');
			foreach ($this->around as $around) {
				$parentCall .= "\n" . $around;
			}

			$parentCall .= "\n" . $this->dumper->format('$__result = $__around->proceed();');
		}

		$this->method->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($parentCall) : $parentCall);

		if ($this->afterThrowing || $this->after) {
			$this->method->addBody('} catch (\Exception $__exception) {');
		}

		if ($this->afterThrowing) {
			foreach ($this->afterThrowing as $afterThrowing) {
				$this->method->addBody(Nette\Utils\Strings::indent($afterThrowing));
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->method->addBody('}');
		}

		if ($this->afterReturning) {
			if ($this->afterThrowing || $this->after) {
				$this->method->addBody('if (empty($__exception)) {');
			}

			foreach ($this->afterReturning as $afterReturning) {
				$this->method->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($afterReturning) : $afterReturning);
			}

			if ($this->afterThrowing || $this->after) {
				$this->method->addBody('}');
			}
		}

		if ($this->after) {
			foreach ($this->after as $after) {
				$this->method->addBody($after);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->method->addBody('if ($__exception) { throw $__exception; }');
		}

		if ($this->method->getReturnType() !== 'void') {
			$this->method->addBody('return $__result;');
		}
	}



	/**
	 * @throws ReflectionException
	 */
	public static function expandTypeHints(ReflectionMethod $from, PointcutMethod $method): PointcutMethod
	{
		$parameters = $method->method->getParameters();

		foreach ($from->getParameters() as $paramRefl) {
			try {
				if (!in_array($parameters[$paramRefl->getName()]->getType(), ['boolean', 'integer', 'float', 'string', 'object', 'int', 'bool' ])) {

					/** @var ReflectionNamedType|null $typehint */
					$typehint = $paramRefl->getType();
					$type = $typehint === null ? '' : $typehint->getName();

					$parameters[$paramRefl->getName()]->setType($type);
				}
			} catch (ReflectionException $e) {
				if (preg_match('#Class (.+) does not exist#', $e->getMessage(), $m)) {
					$parameters[$paramRefl->getName()]->setType('\\' . $m[1]);
				} else {
					throw $e;
				}
			}
		}

		$method->method->setParameters($parameters);

		if (!$method->method->getVisibility()) {
			$method->method->setVisibility('public');
		}

		return $method;
	}


	/**
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		$callable = [$this->method, $name];

		if (!is_callable($callable)) {
			throw new NotImplementedException('Method does not exist!');
		}

		return call_user_func_array($callable, $args);
	}

}
