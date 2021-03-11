<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;

class MyPointcutFilter implements Filter
{

	public function matches(Method $method): bool
	{
		return $method->getClassName() === Legie::class;
	}


	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
