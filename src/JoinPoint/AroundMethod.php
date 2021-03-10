<?php declare(strict_types=1);

namespace Contributte\Aop\JoinPoint;

use Contributte\Aop\PhpGenerator\AdvisedClassType;

class AroundMethod extends MethodInvocation
{

	/** @var array|callable[] */
	private $callChain = [];

	public function __construct($targetObject, $targetMethod, $arguments = [])
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
	}



	public function setArgument($index, $value): void
	{
		$this->arguments[$index] = $value;
	}



	public function addChainLink($object, $method): array
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
