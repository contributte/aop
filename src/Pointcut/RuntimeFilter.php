<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Nette\PhpGenerator\Literal;

interface RuntimeFilter
{

	public function createCondition(): ?Literal;

}
