<?php declare(strict_types = 1);

namespace Contributte\Aop\JoinPoint;

use Throwable;

interface ExceptionAware
{

	public function getException(): ?Throwable;

}
