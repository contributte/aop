<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

class BeforeMethod extends MethodInvocation
{

	public function setArgument(int $index, mixed $value): void
	{
		$this->arguments[$index] = $value;
	}

}
