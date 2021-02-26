<?php


namespace Contributte\Aop\JoinPoint;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BeforeMethod extends MethodInvocation
{

	public function setArgument($index, $value): void
	{
		$this->arguments[$index] = $value;
	}

}
