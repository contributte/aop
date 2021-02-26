<?php


namespace Contributte\Aop\JoinPoint;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface ExceptionAware
{

	function getException(): ?\Throwable;

}
