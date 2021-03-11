<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Contributte\Aop\PhpGenerator\AdvisedClassType;

class AroundMethod extends MethodInvocation
{

	/** @var array|callable[] */
	private array $callChain = [];

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(object $targetObject, string $targetMethod, array $arguments = [])
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
	}


	/**
	 * @param mixed|string $value
	 */
	public function setArgument(int $index, $value): void
	{
		$this->arguments[$index] = $value;
	}


	/**
	 * @return array<int, object|string>
	 */
	public function addChainLink(object $object, string $method): array
	{
		return $this->callChain[] = [$object, $method];
	}


	/**
	 * @return mixed
	 */
	public function proceed()
	{
		if ($callback = array_shift($this->callChain)) {
			return call_user_func([$callback[0], $callback[1]], $this);
		}

		return call_user_func_array([$this->targetObject, AdvisedClassType::CG_PUBLIC_PROXY_PREFIX . $this->targetMethod], $this->getArguments());
	}

}
