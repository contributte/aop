<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

class InheritedClass extends BaseClass
{

	public function __construct($x, $y)
	{
		parent::__construct($x);
	}

}
