<?php declare(strict_types = 1);

namespace Contributte\Aop\PhpGenerator;

use Contributte\Aop\Pointcut\ServiceDefinition;
use Nette;
use Nette\PhpGenerator as Code;

class AdvisedClassType
{

	use Nette\SmartObject;

	public const CG_INJECT_METHOD = '__injectAopContainer';
	public const CG_PUBLIC_PROXY_PREFIX = '__publicAopProxy_';

	public static function setMethodInstance(Code\ClassType $class, Code\Method $method): Code\Method
	{
		$methods = [$method->getName() => $method] + $class->getMethods();
		$class->setMethods($methods);

		return $method;
	}

	public static function generatePublicProxyMethod(Code\ClassType $class, Code\Method $originalMethod): void
	{
		$proxyMethod = new Code\Method(self::CG_PUBLIC_PROXY_PREFIX . $originalMethod->getName());

		$proxyMethod->setVisibility('public');
		$proxyMethod->setComment("@internal\n@deprecated");

		$argumentsPass = [];
		$args = [];
		foreach ($originalMethod->getParameters() as $parameter) {
			if($parameter instanceof Code\PromotedParameter) {
				$promotedParameter = $parameter;
				$parameter = new Code\Parameter($promotedParameter->getName());
				$parameter->setType($promotedParameter->getType());
				$parameter->setDefaultValue($promotedParameter->getDefaultValue());
				$parameter->setNullable($promotedParameter->isNullable());
			}
			/** @var Code\Parameter $parameter */
			$argumentsPass[] = '$' . $parameter->getName();
			$args[$parameter->getName()] = $parameter;
		}

		$proxyMethod->addBody('return parent::?(?);', [$originalMethod->getName(), new Code\PhpLiteral(implode(', ', $argumentsPass))]);

		$proxyMethod->setParameters($args);
		self::setMethodInstance($class, $proxyMethod);
	}

	public static function fromServiceDefinition(ServiceDefinition $service, Code\PhpNamespace $namespace): Code\ClassType
	{
		$originalType = $service->getTypeReflection();
		$class = $namespace->addClass(str_replace(['\\', '.'], '_', $originalType->getName() . 'Class_' . $service->serviceId));

		$class->setExtends('\\' . $originalType->getName());
		$class->setFinal(true);

		$class->addProperty('_contributte_aopContainer')
			->setVisibility('private')
			->addComment('@var \Nette\DI\Container|\SystemContainer');
		$class->addProperty('_contributte_aopAdvices', [])
			->setVisibility('private');

		$injectMethod = $class->addMethod(self::CG_INJECT_METHOD);
		$injectMethod->addParameter('container')->setType(Nette\DI\Container::class);
		$injectMethod->setComment("@internal\n@deprecated");
		$injectMethod->addBody('$this->_contributte_aopContainer = $container;');

		$providerMethod = $class->addMethod('__getAdvice');
		$providerMethod->setVisibility('private');
		$providerMethod->addParameter('name');
		$providerMethod->addBody(
			'if (!isset($this->_contributte_aopAdvices[$name])) {' . "\n\t" .
			'$this->_contributte_aopAdvices[$name] = $this->_contributte_aopContainer->createService($name);' . "\n}\n\n" .
			'return $this->_contributte_aopAdvices[$name];'
		);

		if (!$originalType->hasMethod('__sleep')) {
			$properties = [];
			foreach ($originalType->getProperties() as $property) {
				if ($property->isStatic()) {
					continue;
				}

				$properties[] = "'" . $property->getName() . "'";
			}

			$sleep = $class->addMethod('__sleep');
			$sleep->setBody('return array(?);', [new Code\PhpLiteral(implode(', ', $properties))]);
		}

		return $class;
	}

}
