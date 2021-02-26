<?php


namespace Contributte\Aop\DI;


use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AdviceDefinition
{

	use Nette\SmartObject;

	/**
	 * @var Method
	 */
	private $targetMethod;

	/**
	 * @var Method
	 */
	private $advice;

	/**
	 * @var string
	 */
	private $adviceType;

	/**
	 * @var \Contributte\Aop\Pointcut\Filter
	 */
	private $filter;



	public function __construct(string $adviceType, Method $targetMethod, Method $advice, Filter $filter)
	{
		$this->targetMethod = $targetMethod;
		$this->advice = $advice;
		$this->adviceType = $adviceType;
		$this->filter = $filter;
	}



	public function getAdviceType(): string
	{
		return $this->adviceType;
	}



	public function getTargetMethod(): Method
	{
		return $this->targetMethod;
	}



	public function getAdvice(): Method
	{
		return $this->advice;
	}



	public function getFilter(): Filter
	{
		return $this->filter;
	}

}
