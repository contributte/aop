<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Contributte\Aop\InvalidArgumentException;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FilterMatcher implements Filter
{

	use Nette\SmartObject;

	/**
	 * @var Filter
	 */
	private $filter;



	public function __construct($filterClass)
	{
		if (!in_array(Filter::class, class_implements($filterClass), TRUE)) {
			throw new InvalidArgumentException("Given class '$filterClass' must implement Contributte\\Aop\\Pointcut\\Filter.");
		}

		$this->filter = new $filterClass();
	}



	public function matches(Method $method): bool
	{
		return $this->filter->matches($method);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return $this->filter->listAcceptedTypes();
	}

}
