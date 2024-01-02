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

	private string $operator;

	/** @var array<self|array{0: string|Code\PhpLiteral, 1: string|null, 2: string|Code\PhpLiteral|array<int, Code\PhpLiteral|string>|null}> */
	private array $expressions = [];

	private Code\Dumper $dumper;

	/**
	 * @throws InvalidArgumentException
	 */
	public function __construct(string $operator = self::TYPE_AND)
	{
		if (!in_array($operator = strtoupper($operator), [self::TYPE_AND, self::TYPE_OR], true)) {
			throw new InvalidArgumentException('Given operator \'' . $operator . '\' cannot be evaluated.');
		}

		$this->operator = $operator;
		$this->dumper = new Code\Dumper();
	}

	public static function create(string $operator = self::TYPE_AND): Criteria
	{
		//@phpstan-ignore-next-line
		return new static($operator);
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

	public static function compare(mixed $left, string $operator, mixed $right): bool
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
				}

				if ($right instanceof Traversable) {
					$right = iterator_to_array($right);

				} elseif (!is_array($right)) {
					throw new InvalidArgumentException('Right value is expected to be array or instance of Traversable');
				}

				return in_array($left, $right, true);
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

	/**
	 * @param string|Code\PhpLiteral|array<string|Code\PhpLiteral>|null $right
	 * @throws InvalidArgumentException
	 */
	public function where(string|Code\PhpLiteral|self $left, ?string $comparison = null, string|Code\PhpLiteral|array|null $right = null): self
	{
		if ($left instanceof self) {
			$this->expressions[] = $left;

			return $this;
		}

		if (!self::isValidComparison($comparison = strtoupper((string) $comparison))) {
			throw new InvalidArgumentException('Given comparison \'' . $comparison . '\' cannot be evaluated.');
		}

		$this->expressions[] = [$left, $comparison, $right];

		return $this;
	}

	public function evaluate(ContainerBuilder $builder): bool
	{
		if (empty($this->expressions)) {
			throw new NoRulesExceptions();
		}

		$logical = [];
		/** @var self|array{0: string|Code\PhpLiteral, 1: string|null, 2: string|Code\PhpLiteral|null} $expression */
		foreach ($this->expressions as $expression) {
			$logical[] = $this->doEvaluate($builder, $expression);
			if (!$this->isMatching($logical)) {
				return false;
			}
		}

		return $this->isMatching($logical);
	}

	public function serialize(ContainerBuilder $builder): Code\Literal
	{
		if (empty($this->expressions)) {
			throw new NoRulesExceptions();
		}

		$serialised = [];
		/** @var self|array{0: string|Code\PhpLiteral, 1: string|null, 2: string|Code\PhpLiteral|null} $expression */
		foreach ($this->expressions as $expression) {
			$serialised[] = $this->doSerialize($builder, $expression);
		}

		return new Code\PhpLiteral('(' . implode(' ' . $this->operator . ' ', array_filter($serialised)) . ')');
	}

	/**
	 * @throws ParserException
	 */
	private static function resolveExpression(Code\Literal $expression): mixed
	{
		set_error_handler(function ($severenity, $message): void {
			restore_error_handler();

			throw new ParserException($message, $severenity);
		});
		$result = eval('return ' . $expression . ';');
		restore_error_handler();

		return $result;
	}

	/**
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

	/**
	 * @param bool[] $result
	 */
	private function isMatching(array $result): bool
	{
		if ($this->operator === self::TYPE_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}

	/**
	 * @param self|array{0: string|Code\PhpLiteral, 1: string|null, 2: string|Code\PhpLiteral|null} $expression
	 */
	private function doEvaluate(ContainerBuilder $builder, self|array $expression): bool
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

	private function doEvaluateValueResolve(ContainerBuilder $builder, string|Code\PhpLiteral $expression): mixed
	{
		if ($expression instanceof Code\PhpLiteral) {
			return self::resolveExpression($expression);
		}

		return Nette\DI\Helpers::expand('%' . $expression . '%', $builder->parameters);
	}

	/**
	 * @param array{0: string|Code\PhpLiteral, 1: string|null, 2: string|Code\PhpLiteral|null}|Criteria $expression
	 */
	private function doSerialize(ContainerBuilder $builder, array|Criteria $expression): string|Code\Literal
	{
		if ($expression instanceof self) {
			return $expression->serialize($builder);
		}

		return $this->dumper->format(
			'Criteria::compare(?, ?, ?)',
			$this->doSerializeValueResolve($builder, $expression[0]),
			$expression[1],
			$this->doSerializeValueResolve($builder, $expression[2])
		);
	}

	private function doSerializeValueResolve(ContainerBuilder $builder, string|Code\PhpLiteral $expression): mixed
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
					$targetObject = class_exists($p['context']) || interface_exists($p['context']) ? $this->dumper->format('$this->_contributte_aopContainer->getByType(?)', $p['context']) : $this->dumper->format('$this->_contributte_aopContainer->getService(?)', $p['context']);

					$m['path'] = $p['path'];

				} else {
					throw new NotImplementedException();
				}

				$expression = $this->dumper->format('PropertyAccess::createPropertyAccessor()->getValue(?, ?)', new Code\PhpLiteral($targetObject), $m['path']);
			}

			$expression = new Code\PhpLiteral($expression);
		}

		return $expression;
	}

	public function __toString(): string
	{
		return static::class . '(#' . spl_object_hash($this) . ')';
	}

}
