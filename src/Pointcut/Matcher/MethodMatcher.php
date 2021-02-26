<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Contributte\Aop\InvalidArgumentException;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MethodMatcher implements \Contributte\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $method;

	/**
	 * @var string
	 */
	private $visibility;



	public function __construct($method)
	{
		if (strpos($method, ' ') !== FALSE) {
			list($this->visibility, $method) = explode(' ', $method, 2);
			$this->visibility = strtolower($this->visibility);
			if (!defined('\Contributte\Aop\Pointcut\Method::VISIBILITY_' . strtoupper($this->visibility))) {
				throw new InvalidArgumentException("Invalid visibility '{$this->visibility}'.");
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



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		if ($this->visibility !== NULL && $this->visibility !== $method->getVisibility()) {
			return FALSE;
		}

		return preg_match('~^' . $this->method . '\z~i', $method->getName()) > 0;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
