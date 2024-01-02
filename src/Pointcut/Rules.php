<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Exceptions\NoRulesExceptions;
use Nette;
use Nette\PhpGenerator as Code;

class Rules implements Filter, RuntimeFilter
{

	use Nette\SmartObject;

	public const OP_AND = 'AND';
	public const OP_OR = 'OR';

	private string $operator;

	/** @var Filter[] */
	private array $rules = [];

	/**
	 * @param Filter[] $rules
	 */
	public function __construct(array $rules = [], string $operator = self::OP_AND)
	{
		foreach ($rules as $rule) {
			$this->addRule($rule);
		}

		$this->operator = $operator;
	}

	/**
	 * @param Filter[] $filter
	 */
	public static function unwrap(array $filter, string $operator = self::OP_AND): Filter
	{
		if (is_array($filter)) {
			if (count($filter) > 1) {
				return new Rules($filter, $operator);
			}

			/** @var Filter $filter */
			$filter = reset($filter);
		}

		if ($filter instanceof Rules && count($filter->rules) === 1) {
			return self::unwrap($filter->rules);
		}

		return $filter;
	}

	public function addRule(Filter $rule): void
	{
		$this->rules[] = $rule;
	}

	/**
	 * @return Filter[]
	 */
	public function getRules(): array
	{
		return $this->rules;
	}

	public function matches(Method $method): bool
	{
		if (empty($this->rules)) {
			throw new NoRulesExceptions();
		}

		$logical = [];
		foreach ($this->rules as $rule) {
			$logical[] = $rule->matches($method);
			if (!$this->isMatching($logical)) {
				return false;
			}
		}

		return $this->isMatching($logical);
	}

	/**
	 * @return string[]
	 */
	public function listAcceptedTypes(): array
	{
		$types = [];
		foreach ($this->rules as $rule) {
			$ruleTypes = $rule->listAcceptedTypes();
			if ($ruleTypes !== null) {
				$types = array_merge($types, $ruleTypes);
			}
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

		} elseif (count($conds) === 1) {
			$conds = reset($conds);

		} else {
			return null;
		}

		return new Code\PhpLiteral('(' . $conds . ')');
	}

	/**
	 * @param bool[] $result
	 */
	private function isMatching(array $result): bool
	{
		if ($this->operator === self::OP_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}

}
