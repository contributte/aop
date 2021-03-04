<?php

namespace Contributte\Aop\PhpGenerator;

use Contributte\Aop\After;
use Contributte\Aop\AfterReturning;
use Contributte\Aop\AfterThrowing;
use Contributte\Aop\Around;
use Contributte\Aop\Before;
use Contributte\Aop\DI\AdviceDefinition;
use Contributte\Aop\InvalidArgumentException;
use Contributte\Aop\Pointcut\RuntimeFilter;
use Nette;
use Nette\PhpGenerator as Code;
use ReflectionException;

class PointcutMethod
{

	/** @var array */
	private $before = [];

	/** @var array */
	private $around = [];

	/** @var array */
	private $afterReturning = [];

	/** @var array */
	private $afterThrowing = [];

	/** @var array */
	private $after = [];

	/** @var Code\Method */
	private $method;

	public function __construct(Nette\Reflection\Method $from)
	{
		$this->method = (new Code\Factory())->fromMethodReflection($from);
	}

	public static function from(Nette\Reflection\Method $from): PointcutMethod
	{
		$method = new self($from);
		$params = [];
		$factory = new Code\Factory();
		foreach ($from->getParameters() as $param) {
			$params[$param->getName()] = $factory->fromParameterReflection($param);
		}

		$method->setParameters($params);
		if ($from instanceof Nette\Reflection\Method) {
			$isInterface = $from->getDeclaringClass()->isInterface();
			$method->setStatic($from->isStatic());
			$method->setVisibility($from->isPrivate() ? 'private' : ($from->isProtected() ? 'protected' : ($isInterface ? null : 'public')));
			$method->setFinal($from->isFinal());
			$method->setAbstract($from->isAbstract() && !$isInterface);
			$method->setBody($from->isAbstract() ? false : '');
		}

		$method->setReturnReference($from->returnsReference());
		$method->setVariadic($from->isVariadic());
		$method->setComment(Code\Helpers::unformatDocComment($from->getDocComment()));
		if ($from->hasReturnType()) {
			$method->setReturnType(($from->getReturnType()->getName()));
			$method->setReturnNullable($from->getReturnType()->allowsNull());
		}

		return $method;
	}


	public function addAdvice(AdviceDefinition $adviceDef): void
	{
		$adviceMethod = $adviceDef->getAdvice();

		switch ($adviceDef->getAdviceType()) {
			case Before::getClassName():
				$this->before[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?($__before = new \Contributte\Aop\JoinPoint\BeforeMethod($this, __FUNCTION__, $__arguments));' . "\n" .
					'$__arguments = $__before->getArguments();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));

				break;

			case Around::getClassName():
				$this->around[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$__around->addChainLink($this->__getAdvice(?), ?);',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case AfterReturning::getClassName():
				$this->afterReturning[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?($__afterReturning = new \Contributte\Aop\JoinPoint\AfterReturning($this, __FUNCTION__, $__arguments, $__result));' . "\n" .
					'$__result = $__afterReturning->getResult();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case AfterThrowing::getClassName():
				$this->afterThrowing[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?(new \Contributte\Aop\JoinPoint\AfterThrowing($this, __FUNCTION__, $__arguments, $__exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case After::getClassName():
				$this->after[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
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

		} elseif (!$condition = $filter->createCondition()) {
			return $code;
		}

		foreach ($adviceDef->getTargetMethod()->getParameterNames() as $i => $name) {
			$condition = str_replace('$' . $name, '$__arguments[' . $i . ']', $condition);
		}

		return Code\Helpers::format("if ? {\n?\n}", new Code\PhpLiteral($condition), new Code\PhpLiteral(Nette\Utils\Strings::indent($code)));
	}



	public function beforePrint(): void
	{
		$this->setBody('');

		if (strtolower($this->getName()) === '__construct') {
			$this->addParameter('_contributte_aopContainer')
				->setTypeHint(Nette\DI\Container::class);
			$this->addBody('$this->_contributte_aopContainer = $_contributte_aopContainer;');
		}

		$this->addBody('$__arguments = func_get_args(); $__exception = $__result = NULL;');

		if ($this->before) {
			foreach ($this->before as $before) {
				$this->addBody($before);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('try {');
		}

		if (!$this->around) {
			$parentCall = Code\Helpers::format('$__result = call_user_func_array("parent::?", $__arguments);', $this->getName());
		} else {
			$parentCall = Code\Helpers::format('$__around = new \Contributte\Aop\JoinPoint\AroundMethod($this, __FUNCTION__, $__arguments);');
			foreach ($this->around as $around) {
				$parentCall .= "\n" . $around;
			}

			$parentCall .= "\n" . Code\Helpers::format('$__result = $__around->proceed();');
		}

		$this->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($parentCall) : $parentCall);

		if ($this->afterThrowing || $this->after) {
			$this->addBody('} catch (\Exception $__exception) {');
		}

		if ($this->afterThrowing) {
			foreach ($this->afterThrowing as $afterThrowing) {
				$this->addBody(Nette\Utils\Strings::indent($afterThrowing));
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('}');
		}

		if ($this->afterReturning) {
			if ($this->afterThrowing || $this->after) {
				$this->addBody('if (empty($__exception)) {');
			}

			foreach ($this->afterReturning as $afterReturning) {
				$this->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($afterReturning) : $afterReturning);
			}

			if ($this->afterThrowing || $this->after) {
				$this->addBody('}');
			}
		}

		if ($this->after) {
			foreach ($this->after as $after) {
				$this->addBody($after);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('if ($__exception) { throw $__exception; }');
		}

		if ($this->getReturnType() !== 'void') {
			$this->addBody('return $__result;');
		}
	}



	/**
	 * @throws ReflectionException
	 */
	public static function expandTypeHints(Nette\Reflection\Method $from, PointcutMethod $method): PointcutMethod
	{
		$parameters = $method->getParameters();
		/** @var Code\Parameter[] $parameters */

		foreach ($from->getParameters() as $paramRefl) {
			try {
				if (!in_array($parameters[$paramRefl->getName()]->getTypeHint(), ['boolean', 'integer', 'float', 'string', 'object', 'int', 'bool' ])) {
					if (PHP_VERSION_ID >= 80000) {
						$type = $paramRefl->getType() ? $paramRefl->getType()->getName() : '';
					} else {
						$type = $paramRefl->isArray() ? 'array' : ($paramRefl->getClass() ? '\\' . $paramRefl->getClass()->getName() : '');
					}

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

		$method->setParameters($parameters);

		if (!$method->getVisibility()) {
			$method->setVisibility('public');
		}

		return $method;
	}

	public function __call(string $name, array $args)
	{
		return call_user_func_array([$this->method, $name], $args);
	}

}