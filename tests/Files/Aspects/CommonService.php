<?php declare(strict_types = 1);

namespace Tests\Files\Aspects;

use RuntimeException;

class CommonService
{

	/** @var mixed[] */
	public array $calls = [];

	public bool $throw = false;

	public int $return = 2;

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
