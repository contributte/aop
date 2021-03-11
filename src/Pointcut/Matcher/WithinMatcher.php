<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class WithinMatcher implements Filter
{

	use Nette\SmartObject;

	private ?string $type = null;

	private string $pattern;

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
	 * @return array<int, string|Filter>
	 */
	public function listAcceptedTypes(): array
	{
		if ($this->type) {
			return [$this->type];
		}

		return [];
	}

}
