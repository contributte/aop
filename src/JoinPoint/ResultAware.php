<?php declare(strict_types=1);

namespace Contributte\Aop\JoinPoint;

interface ResultAware
{

	/**
	 * @return mixed
	 */
	function getResult();

}
