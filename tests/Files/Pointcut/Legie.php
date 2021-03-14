<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

class Legie implements Rimmer, Lister, Kryten, Cat
{

	#[Test]

	public function publicCalculation(): void
	{
	}


	protected function protectedCalculation(): void
	{
	}

	//phpcs:ignore
	private function privateCalculation()
	{
	}


	public function injectBar(): void
	{
	}

}
