<?php

namespace Contributte\Aop\Pointcut;

use Nette;

interface RuntimeFilter
{

	function createCondition(): ?Nette\PhpGenerator\Literal;

}
