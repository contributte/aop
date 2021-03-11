<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class MethodMatcher implements Filter
{

	use Nette\SmartObject;

	/** @var string */
	private $method;

	/** @var string */
	private $visibility;

	public function __construct($method)
	{
		if (strpos($method, ' ') !== false) {
			[$this->visibility, $method] = explode(' ', $method, 2);
			$this->visibility = strtolower($this->visibility);
			if (!defined('\Contributte\Aop\Pointcut\Method::VISIBILITY_' . strtoupper($this->visibility))) {
				throw new InvalidArgumentException('Invalid visibility \' ' . $this->visibility . '\'.');
			}
		}

		// preg_replace($pattern, $replacement, $subject, $limit);
		$method = preg_replace([
			'~\\\\\\*~',
			'~\\\\\\[\\\\\\!(.*?)\\\\\\]~', // restrict
			'~\\\\\\[\\\\\\?(.*?)\\\\\\]~', // optional
		], [
			'.*?',
			'(?!$1)',
			'(?:$1)?',
		], preg_quote($method));

		if (preg_match_all('~\\\\\\[(?!\\\\\\!|\\\\\\?|\s)(?:\\\\\\||[^\\|]*?)+\\\\\\]~', $method, $m, PREG_SET_ORDER)) {
			$method = str_replace($m[0][0], '(?:' . preg_replace('~\\\\\\|~', '|', substr($m[0][0], 2, -2)) . ')', $method);
		}

		$this->method = $method;
	}



	public function matches(Method $method): bool
	{
		if ($this->visibility !== null && $this->visibility !== $method->getVisibility()) {
			return false;
		}

		return preg_match('~^' . $this->method . '\z~i', $method->getName()) > 0;
	}


	/**
	 * @return array<int, string|Filter>
	 */
	public function listAcceptedTypes(): array
	{
		return [];
	}

}
