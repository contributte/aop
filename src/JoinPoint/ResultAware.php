<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

interface ResultAware
{

	public function getResult(): mixed;

}
