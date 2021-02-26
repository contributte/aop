<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EvaluateMatcher implements \Contributte\Aop\Pointcut\Filter, \Contributte\Aop\Pointcut\RuntimeFilter
{

	use Nette\SmartObject;

	/**
	 * @var Criteria
	 */
	private $evaluate;

	/**
	 * @var \Nette\DI\ContainerBuilder
	 */
	private $builder;



	public function __construct(Criteria $criteria, Nette\DI\ContainerBuilder $builder)
	{
		$this->evaluate = $criteria;
		$this->builder = $builder;
	}



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		return TRUE;
	}



	public function createCondition(): ?Nette\PhpGenerator\Literal
	{
		return $this->evaluate->serialize($this->builder);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
