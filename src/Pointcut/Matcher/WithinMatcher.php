<?php

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class WithinMatcher implements Filter
{

	use Nette\SmartObject;

	/** @var string */
	private $type;

	/** @var string */
	private $pattern;

	public function __construct(string $type)
	{
		if (strpos($type, '*') !== false) {
			$this->pattern = str_replace('\\*', '.*', preg_quote($type));

		} else {
			$this->type = Nette\Reflection\ClassType::from($type)->getName();
		}
	}



	public function matches(Method $method): bool
	{
		if ($this->type !== null) {
			return isset($method->typesWithin[$this->type]);
		}

		foreach ($method->typesWithin as $within) {
			if (preg_match('~^' . $this->pattern . '\z~i', $within)) {
				return true;
			}
		}

		return false;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		if ($this->type) {
			return [$this->type];
		}

		return false;
	}

}
