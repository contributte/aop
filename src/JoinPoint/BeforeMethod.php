<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

class BeforeMethod extends MethodInvocation
{

	/**
	 * @param mixed|string $value
	 */
	public function setArgument(int $index, $value): void
	{
		$this->arguments[$index] = $value;
	}

}
