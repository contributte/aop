<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use RuntimeException;

class CommonService
{

	public $calls = [];

	public $throw = false;

	public $return = 2;

	public function __construct()
	{
	}


	public function magic(?int $argument): ?int
	{
		$this->calls[] = func_get_args();

		if ($this->throw) {
			throw new RuntimeException("Something's fucky");
		}

		return $this->return * $argument;
	}

}
