<?php

namespace Contributte\Aop\JoinPoint;

use Throwable;

interface ExceptionAware
{

	function getException(): ?Throwable;

}
