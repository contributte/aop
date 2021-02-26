<?php


namespace Contributte\Aop\JoinPoint;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface ResultAware
{

	/**
	 * @return mixed
	 */
	function getResult();

}
