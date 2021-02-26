<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class WithinMatcher implements \Contributte\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $pattern;



	public function __construct(string $type)
	{
		if (strpos($type, '*') !== FALSE) {
			$this->pattern = str_replace('\\*', '.*', preg_quote($type));

		} else {
			$this->type = Nette\Reflection\ClassType::from($type)->getName();
		}
	}



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		if ($this->type !== NULL) {
			return isset($method->typesWithin[$this->type]);
		}

		foreach ($method->typesWithin as $within) {
			if (preg_match('~^' . $this->pattern . '\z~i', $within)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		if ($this->type) {
			return [$this->type];
		}

		return FALSE;
	}

}
