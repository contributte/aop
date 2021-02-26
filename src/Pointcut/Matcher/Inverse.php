<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Inverse implements \Contributte\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var \Contributte\Aop\Pointcut\Filter
	 */
	private $filter;



	public function __construct(\Contributte\Aop\Pointcut\Filter $filter)
	{
		$this->filter = $filter;
	}



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		return !$this->filter->matches($method);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return $this->filter->listAcceptedTypes();
	}

}
