<?php


namespace Contributte\Aop\Pointcut;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface RuntimeFilter
{

	function createCondition(): ?Nette\PhpGenerator\Literal;

}
