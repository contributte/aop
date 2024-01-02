<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class MethodAttributedWithMatcher implements Filter
{

	use Nette\SmartObject;

	private string $attributeClass;

	public function __construct(string $attributeClass)
	{
		$this->attributeClass = $attributeClass;
	}

	public function matches(Method $method): bool
	{
		foreach ($method->getAttributes() as $attribute) {
			if (!$attribute->newInstance() instanceof $this->attributeClass) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
