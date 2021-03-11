<?php declare(strict_types = 1);

namespace Contributte\Aop\DI;

use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Method;
use Nette;

class AdviceDefinition
{

	use Nette\SmartObject;

	private Method $targetMethod;

	private Method $advice;

	private string $adviceType;

	private Filter $filter;

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
