<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Contributte\Aop\Exceptions\UnexpectedValueException;
use Contributte\Aop\PhpGenerator\AdvisedClassType;

class AroundMethod extends MethodInvocation
{

	/** @var callable[] */
	private array $callChain = [];

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [])
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
	}

	public function setArgument(int $index, mixed $value): void
	{
		$this->arguments[$index] = $value;
	}

	/**
	 * @return callable[]
	 */
	public function addChainLink(object $object, string $method): array
	{
		$callable = [$object, $method];
		if (is_callable($callable)) {
			$this->callChain[] = $callable;
		}

		return $this->callChain;
	}

	public function proceed(): mixed
	{
		if ($callback = array_shift($this->callChain)) {
			return call_user_func($callback, $this);
		}

		$callable = [$this->targetObject, AdvisedClassType::CG_PUBLIC_PROXY_PREFIX . $this->targetMethod];

		if (!is_callable($callable)) {
			throw new UnexpectedValueException('Cannot proceed, compiler error!');
		}

		return call_user_func_array($callable, $this->getArguments());
	}

}
