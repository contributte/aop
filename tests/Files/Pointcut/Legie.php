<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

class Legie implements Rimmer, Lister, Kryten, Cat
{

	/**
	 * @Test()
	 */
	public function publicCalculation()
	{
	}


	protected function protectedCalculation()
	{
	}


	private function privateCalculation()
	{
	}


	public function injectBar()
	{
	}

}
