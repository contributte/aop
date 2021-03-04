<?php

namespace Contributte\Aop\JoinPoint;

interface ResultAware
{

	/**
	 * @return mixed
	 */
	function getResult();

}
