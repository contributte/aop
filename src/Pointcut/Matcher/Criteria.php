<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut\Matcher;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Exceptions\NoRulesExceptions;
use Contributte\Aop\Exceptions\NotImplementedException;
use Contributte\Aop\Exceptions\ParserException;
use Doctrine\Common\Collections\Collection;
use Nette;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator as Code;
use SplObjectStorage;
use Traversable;

class Criteria
{

	use Nette\SmartObject;

	public const TYPE_AND = 'AND';
	public const TYPE_OR = 'OR';

	public const EQ = '=='; // value comparison
	public const NEQ = '<>';
	public const LT = '<';
	public const LTE = '<=';
	public const GT = '>';
	public const GTE = '>=';
	public const IS = '==='; // identity comparison
	public const IN = 'IN';
	public const NIN = 'NIN';
	public const CONTAINS = 'CONTAINS';
	public const MATCHES = 'MATCHES';

	/** @var string */
	private $operator;

	/** @var array */
	private $expressions = [];

	/**
	 * @param string $operator
	 * @throws InvalidArgumentException
	 */
	public function __construct($operator = self::TYPE_AND)
	{
		if (!in_array($operator = strtoupper($operator), [self::TYPE_AND, self::TYPE_OR], true)) {
			throw new InvalidArgumentException('Given operator \'' . $operator . '\' cannot be evaluated.');
		}

		$this->operator = $operator;
	}



	/**
	 * @param string|self $left
	 * @param string|self|null $right
	 * @return Criteria
	 * @throws InvalidArgumentException
	 */
	public function where($left, ?string $comparison = null, $right = null): self
	{
		if ($left instanceof self) {
			$this->expressions[] = $left;
			return $this;
		}

		if (!self::isValidComparison($comparison = strtoupper($comparison))) {
			throw new InvalidArgumentException('Given comparison \'' . $comparison . '\' cannot be evaluated.');
		}

		$this->expressions[] = [$left, $comparison, $right];
		return $this;
	}



	public static function create(string $operator = self::TYPE_AND): Criteria
	{
		//@phpstan-ignore-next-line
		return new static($operator);
	}



	public function evaluate(ContainerBuilder $builder): bool
	{
		if (empty($this->expressions)) {
			throw new NoRulesExceptions();
		}

		$logical = [];
		foreach ($this->expressions as $expression) {
			$logical[] = $this->doEvaluate($builder, $expression);
			if (!$this->isMatching($logical)) {
				return false;
			}
		}

		return $this->isMatching($logical);
	}



	private function isMatching(array $result): bool
	{
		if ($this->operator === self::TYPE_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}



	/**
	 * @param array|Criteria $expression
	 */
	private function doEvaluate(ContainerBuilder $builder, $expression): bool
	{
		if ($expression instanceof self) {
			return $expression->evaluate($builder);
		}

		return self::compare(
			$this->doEvaluateValueResolve($builder, $expression[0]),
			$expression[1],
			$this->doEvaluateValueResolve($builder, $expression[2])
		);
	}



	private function doEvaluateValueResolve(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof Code\PhpLiteral) {
			return self::resolveExpression($expression);
		}

		return Nette\DI\Helpers::expand('%' . $expression . '%', $builder->parameters);
	}



	public function serialize(ContainerBuilder $builder): Code\Literal
	{
		if (empty($this->expressions)) {
			throw new NoRulesExceptions();
		}

		$serialised = [];
		foreach ($this->expressions as $expression) {
			$serialised[] = $this->doSerialize($builder, $expression);
		}

		return new Code\PhpLiteral('(' . implode(' ' . $this->operator . ' ', array_filter($serialised)) . ')');
	}



	/**
	 * @param array|Criteria $expression
	 * @return string|Code\Literal
	 */
	private function doSerialize(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof self) {
			return $expression->serialize($builder);
		}

		return Code\Helpers::format(
			'Criteria::compare(?, ?, ?)',
			$this->doSerializeValueResolve($builder, $expression[0]),
			$expression[1],
			$this->doSerializeValueResolve($builder, $expression[2])
		);
	}



	private function doSerializeValueResolve(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof Code\PhpLiteral) {
			$expression = self::resolveExpression($expression);

		} elseif (substr($expression, 0, 1) === '%') {
			$expression = Nette\DI\Helpers::expand($expression, $builder->parameters);

		} elseif (substr($expression, 0, 1) === '$') {
			$expression = new Code\PhpLiteral($expression);

		} else {
			if (!$m = self::shiftAccessPath($expression)) {
				return $expression; // it's probably some kind of expression

			} else {
				if ($m['context'] === 'this') {
					$targetObject = '$this';

				} elseif ($m['context'] === 'context' && ($p = self::shiftAccessPath($m['path']))) {
					$targetObject = class_exists($p['context']) || interface_exists($p['context']) ? Code\Helpers::format('$this->_contributte_aopContainer->getByType(?)', $p['context']) : Code\Helpers::format('$this->_contributte_aopContainer->getService(?)', $p['context']);

					$m['path'] = $p['path'];

				} else {
					throw new NotImplementedException();
				}

				$expression = Code\Helpers::format('PropertyAccess::createPropertyAccessor()->getValue(?, ?)', new Code\PhpLiteral($targetObject), $m['path']);
			}

			$expression = new Code\PhpLiteral($expression);
		}

		return $expression;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return static::class . '(#' . spl_object_hash($this) . ')';
	}



	public static function isValidComparison(string $comparison): bool
	{
		return in_array(strtoupper($comparison), [
			self::EQ,
		self::NEQ,
		'!=',
			self::LT,
		self::LTE,
			self::GT,
		self::GTE,
			self::IS,
		'IS',
		self::IN,
		self::NIN,
			self::CONTAINS,
		self::MATCHES,
		], true);
	}



	/**
	 * @return mixed
	 * @throws ParserException
	 */
	private static function resolveExpression(Code\Literal $expression)
	{
		set_error_handler(function ($severenity, $message) {
			restore_error_handler();
			throw new ParserException($message, $severenity);
		});
		$result = eval('return ' . $expression . ';');
		restore_error_handler();

		return $result;
	}



	/**
	 * @param string $path
	 * @return array<string, string>|NULL
	 */
	private static function shiftAccessPath(string $path): ?array
	{
		$shifted = Nette\Utils\Strings::match($path, '~^(?P<context>[^\\[\\]\\.]+)(?P<path>(\\[|\\.).*)\z~i');
		if ($shifted && substr($shifted['path'], 0, 1) === '.') {
			$shifted['path'] = substr($shifted['path'], 1);
		}

		return $shifted;
	}



	public static function compare($left, string $operator, $right)
	{
		switch (strtoupper($operator)) {
			case self::EQ:
				return $left === $right;

			case self::NEQ:
			case '!=':
				return !self::compare($left, self::EQ, $right);

			case self::GT:
				return $left > $right;

			case self::GTE:
				return $left >= $right;

			case self::LT:
				return $left < $right;

			case self::LTE:
				return $left <= $right;

			case self::IS:
			case 'IS':
				return $left === $right;

			case self::NIN:
				return !self::compare($left, self::IN, $right);

			case self::IN:
				if ($right instanceof SplObjectStorage || $right instanceof Collection) {
					return $left !== null && $right->contains($left);

				} else {
					if ($right instanceof Traversable) {
						$right = iterator_to_array($right);

					} elseif (!is_array($right)) {
						throw new InvalidArgumentException('Right value is expected to be array or instance of Traversable');
					}

					return in_array($left, $right, true);
				}
			case self::CONTAINS:
				return self::compare($right, self::IN, $left);

			case self::MATCHES:
				if ($right instanceof Traversable) {
					$right = iterator_to_array($right);

				} elseif (!is_array($right)) {
					throw new InvalidArgumentException('Right value is expected to be array or Traversable');
				}

				if ($left instanceof Traversable) {
					$left = iterator_to_array($left);

				} elseif (!is_array($left)) {
					throw new InvalidArgumentException('Left value is expected to be array or Traversable');
				}

				return (bool) array_filter(array_intersect($left, $right));

			default:
				throw new NotImplementedException();
		}
	}

}
