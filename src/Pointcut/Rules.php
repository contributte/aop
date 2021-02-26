<?php


namespace Contributte\Aop\Pointcut;


use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Rules implements Filter, RuntimeFilter
{

	use Nette\SmartObject;

	const OP_AND = 'AND';
	const OP_OR = 'OR';

	/**
	 * @var string
	 */
	private $operator;

	/**
	 * @var array|Filter[]
	 */
	private $rules;



	public function __construct(array $rules = [], $operator = self::OP_AND)
	{
		foreach ($rules as $rule) {
			$this->addRule($rule);
		}

		$this->operator = $operator;
	}



	public function addRule(Filter $rule): void
	{
		$this->rules[] = $rule;
	}



	/**
	 * @return \Contributte\Aop\Pointcut\Filter[]
	 */
	public function getRules(): array
	{
		return $this->rules;
	}


	public function matches(Method $method): bool
	{
		if (empty($this->rules)) {
			throw new \Contributte\Aop\NoRulesExceptions();
		}

		$logical = [];
		foreach ($this->rules as $rule) {
			$logical[] = $rule->matches($method);
			if (!$this->isMatching($logical)) {
				return FALSE;
			}
		}

		return $this->isMatching($logical);
	}



	/**
	 * @return array
	 */
	public function listAcceptedTypes()
	{
		$types = [];
		foreach ($this->rules as $rule) {
			$types = array_merge($types, (array)$rule->listAcceptedTypes());
		}

		return array_filter($types);
	}



	public function createCondition(): ?Code\Literal
	{
		$conds = [];
		foreach ($this->rules as $rule) {
			if (!$rule instanceof RuntimeFilter) {
				continue;
			}

			$conds[] = $rule->createCondition();
		}

		$conds = array_filter($conds);

		if (count($conds) > 1) {
			$conds = implode(' ' . $this->operator . ' ', $conds);

		} elseif (count($conds) == 1) {
			$conds = reset($conds);

		} else {
			return NULL;
		}

		return new Code\PhpLiteral('(' . $conds . ')');
	}



	/**
	 * @param array|string|Filter $filter
	 * @param string $operator
	 * @return Filter
	 */
	public static function unwrap($filter, $operator = self::OP_AND)
	{
		if (is_array($filter)) {
			if (count($filter) > 1) {
				return new Rules($filter, $operator);
			}

			$filter = reset($filter);
		}

		if ($filter instanceof Rules && count($filter->rules) === 1) {
			return self::unwrap($filter->rules);
		}

		return $filter;
	}



	private function isMatching(array $result)
	{
		if ($this->operator === self::OP_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}

}
