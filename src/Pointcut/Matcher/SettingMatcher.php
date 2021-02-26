<?php


namespace Contributte\Aop\Pointcut\Matcher;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class SettingMatcher implements \Contributte\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var Criteria
	 */
	private $settings;

	/**
	 * @var \Nette\DI\ContainerBuilder
	 */
	private $builder;



	public function __construct(Criteria $criteria, Nette\DI\ContainerBuilder $builder)
	{
		$this->settings = $criteria;
		$this->builder = $builder;
	}



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		return $this->settings->evaluate($this->builder);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
