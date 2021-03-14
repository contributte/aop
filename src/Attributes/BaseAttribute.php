<?php declare(strict_types = 1);

namespace Contributte\Aop\Attributes;

use Attribute;

#[Attribute]
class BaseAttribute
{

	private string $value;

	public function __construct(string $value = '')
	{
		$this->value = $value;
	}


	public static function getClassName(): string
	{
		return static::class;
	}

	public function getValue(): string
	{
		return $this->value;
	}

}
