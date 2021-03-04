<?php

namespace Contributte\Aop\JoinPoint;

class BeforeMethod extends MethodInvocation
{

	public function setArgument($index, $value): void
	{
		$this->arguments[$index] = $value;
	}

}
