<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

use Contributte\Aop\Exceptions\InvalidArgumentException;
use Contributte\Aop\Exceptions\ParserException;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Nette;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Tokenizer\Stream;
use Nette\Tokenizer\Tokenizer;

class Parser
{

	use Nette\SmartObject;

	public const TOK_BRACKET = 'bracket';
	public const TOK_VISIBILITY = 'visibility';
	public const TOK_KEYWORD = 'keyword';
	public const TOK_OPERATOR = 'operator';
	public const TOK_LOGIC = 'logic';
	public const TOK_NOT = 'not';
	public const TOK_METHOD = 'method';
	public const TOK_IDENTIFIER = 'identifier';
	public const TOK_WHITESPACE = 'whitespace';
	public const TOK_STRING = 'string';
	public const TOK_WILDCARD = 'wildcard';

	private Tokenizer $tokenizer;

	private MatcherFactory $matcherFactory;

	public function __construct(MatcherFactory $matcherFactory)
	{
		$this->tokenizer = new Tokenizer([
			self::TOK_BRACKET => '[\\(\\)]',
			self::TOK_VISIBILITY => '(?:public|protected|private)(?=[\t ]+)',
			self::TOK_KEYWORD => '(?:classAttributedWith|class|methodAttributedWith|method|within|filter|setting|evaluate)(?=\\()',
			self::TOK_OPERATOR => '(?:===?|!==?|<=|>=|<|>|n?in|contains|matches)',
			self::TOK_LOGIC => '(?:\\&\\&|\\|\\||,)',
			self::TOK_NOT => '!',
			self::TOK_METHOD => '(?:-\\>|::)[_a-z0-9\\*\\[\\]\\|\\!]+(?=(?:\\(|\\)|\s|\z))', // including wildcard
			self::TOK_IDENTIFIER => '[_a-z0-9\x7F-\xFF\\*\\.\\$\\%\\\\-]+(?<!\\-)', // including wildcard
			self::TOK_WHITESPACE => '[\n\r\s]+',
			self::TOK_STRING => '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"',
			self::TOK_WILDCARD => '\\*',
		], 'i');

		$this->matcherFactory = $matcherFactory;
	}

	public function parse(string $input): mixed
	{
		try {
			$tokens = $this->tokenizer->tokenize($input);
			$tokens->ignored = [self::TOK_WHITESPACE];

		} catch (Nette\Tokenizer\Exception $e) {
			throw new ParserException('Input contains unexpected expressions', 0, $e);
		}

		return $this->doParse($tokens);
	}

	protected static function sanitizeArgumentExpression(mixed $value, Nette\Tokenizer\Token $token): string|PhpLiteral
	{
		if ($token->type === self::TOK_STRING || is_numeric($value) || preg_match('~^(TRUE|FALSE)\z~i', $value)) {
			return new PhpLiteral($value);
		}

		return $value;
	}

	/**
	 * @param string[]|string $types
	 * @param string[]|string $allowedToSkip
	 * @throws ParserException
	 */
	protected static function nextValue(Stream $tokens, array|string $types, array|string $allowedToSkip = []): ?string
	{
		do {
			if (call_user_func_array([$tokens, 'isCurrent'], (array) $types)) {
				return $tokens->currentValue();
			}

			if (!$allowedToSkip || !call_user_func_array([$tokens, 'isCurrent'], (array) $allowedToSkip)) {
				$type = $tokens->currentToken();

				throw new ParserException('Unexpected token ' . $type->type . ' at offset ' . $type->offset);
			}
		} while ($tokens->nextToken());

		throw new ParserException('Expected token ' . implode(', ', (array) $types));
	}

	/**
	 * @throws ParserException
	 */
	protected function doParse(Stream $tokens): mixed
	{
		$inverseNext = false;
		$operator = null;
		$rules = [];
		while ($token = $tokens->nextToken()) {
			if ($tokens->isCurrent(self::TOK_KEYWORD)) {
				$rule = $this->{'parse' . $token->value}($tokens);
				if ($inverseNext) {
					$rule = new Matcher\Inverse($rule);
					$inverseNext = false;
				}

				$rules[] = $rule;

			} elseif ($tokens->isCurrent(self::TOK_IDENTIFIER)) {
				$rule = $this->parseMethod($tokens);
				if ($inverseNext) {
					$rule = new Matcher\Inverse($rule);
					$inverseNext = false;
				}

				$rules[] = $rule;

			} elseif ($tokens->isCurrent('(')) {
				$rules[] = $this->doParse($tokens);

			} elseif ($tokens->isCurrent(')')) {
				break;

			} elseif ($tokens->isCurrent(self::TOK_NOT)) {
				$inverseNext = true;

			} elseif ($tokens->isCurrent(self::TOK_LOGIC)) {
				if ($operator !== null && $operator !== $tokens->currentValue()) {
					throw new ParserException('Unexpected operator ' . $tokens->currentValue() . '. If you wanna combine them, you must wrap them in brackets like this `a || (b && c)`.');
				}

				$operator = $tokens->currentValue();

				continue;
			}
		}

		if ($operator === ',' || $operator === '&&') {
			$operator = Rules::OP_AND;

		} elseif ($operator === '||') {
			$operator = Rules::OP_OR;
		}

		return Rules::unwrap($rules, $operator ? : Rules::OP_AND);
	}

	protected function parseClass(Stream $tokens): Filter
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$className = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('class', $className);
	}

	protected function parseMethod(Stream $tokens): Filter
	{
		$visibility = null;
		$arguments = [];

		if ($tokens->isCurrent(self::TOK_KEYWORD)) {
			self::nextValue($tokens, self::TOK_KEYWORD, [self::TOK_WHITESPACE]);
			$tokens->nextToken();
			self::nextValue($tokens, '(', [self::TOK_WHITESPACE]);
			$tokens->nextToken();
		}

		$className = self::nextValue($tokens, [self::TOK_IDENTIFIER, self::TOK_VISIBILITY], [self::TOK_WHITESPACE]);
		if ($tokens->isCurrent(self::TOK_VISIBILITY)) {
			$visibility = $className . ' ';

			$tokens->nextToken();
			$className = self::nextValue($tokens, [self::TOK_IDENTIFIER], [self::TOK_WHITESPACE]);
		}

		$tokens->nextToken();
		$method = substr(self::nextValue($tokens, [self::TOK_METHOD], [self::TOK_WHITESPACE]), 2);

		if ($tokens->isNext('(')) {
			if ($criteria = $this->parseArguments($tokens)) {
				$arguments = [$this->matcherFactory->getMatcher('arguments', $criteria)];
			}
		}

		$tokens->nextToken(); // method end )

		if ($method === '*' && empty($visibility) && !$arguments) {
			return $this->matcherFactory->getMatcher('class', $className);
		} elseif ($className === '*' && !$arguments) {
			return $this->matcherFactory->getMatcher('method', $visibility . $method);
		}

		return new Rules(array_merge([
			$this->matcherFactory->getMatcher('class', $className),
			$this->matcherFactory->getMatcher('method', $visibility . $method),
		], $arguments), Rules::OP_AND);
	}

	protected function parseWithin(Stream $tokens): Filter
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$within = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('within', $within);
	}

	protected function parseFilter(Stream $tokens): Filter
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$filter = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('filter', $filter);
	}

	protected function parseSetting(Stream $tokens): Filter
	{
		$tokens->nextUntil('(');
		if (!$criteria = $this->parseArguments($tokens)) {
			throw new ParserException('Settings criteria cannot be empty.');
		}

		return $this->matcherFactory->getMatcher('setting', $criteria);
	}

	protected function parseEvaluate(Stream $tokens): Filter
	{
		$tokens->nextUntil('(');
		if (!$criteria = $this->parseArguments($tokens)) {
			throw new ParserException('Evaluate expression cannot be empty.');
		}

		return $this->matcherFactory->getMatcher('evaluate', $criteria);
	}

	protected function parseClassAttributedWith(Stream $tokens): Filter
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$attributte = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('classAttributedWith', $attributte);
	}

	protected function parseMethodAttributedWith(Stream $tokens): Filter
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$attribute = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('methodAttributedWith', $attribute);
	}

	protected function parseArguments(Stream $tokens): ?Criteria
	{
		$operator = null;
		$conditions = [];

		while ($token = $tokens->nextToken()) {
			if ($tokens->isCurrent(self::TOK_LOGIC)) {
				if ($operator !== null && $operator !== $tokens->currentValue()) {
					throw new ParserException('Unexpected operator ' . $tokens->currentValue() . '. If you wanna combine them, you must wrap them in brackets.');
				}

				$operator = $tokens->currentValue();

				continue;
			}

			if ($tokens->isCurrent('(')) {
				if ($conditions || $tokens->isNext('(')) {
					$conditions[] = $this->parseArguments($tokens);
				}

				continue;
			}

			if ($tokens->isCurrent(')')) {
				break;
			}

			$left = self::sanitizeArgumentExpression(self::nextValue(
				$tokens,
				[self::TOK_IDENTIFIER, self::TOK_STRING],
				self::TOK_WHITESPACE
			), $tokens->currentToken());

			$tokens->nextToken();
			$comparator = self::nextValue($tokens, [self::TOK_OPERATOR, self::TOK_LOGIC, ')'], self::TOK_WHITESPACE);

			if ($tokens->isCurrent(self::TOK_LOGIC, ')')) {
				$tokens->position -= 1;
				$conditions[] = [$left, Matcher\Criteria::EQ, new PhpLiteral('TRUE')];

				continue;
			}

			if ($tokens->isCurrent('in', 'nin', 'matches')) {
				$tokens->nextUntil(self::TOK_IDENTIFIER, self::TOK_STRING, '(');
				if ($tokens->isNext('(')) {
					$tokens->nextToken(); // (

					$right = [];
					while ($token = $tokens->nextToken()) {
						if ($tokens->isCurrent(')')) {
							break;
						}

						if ($tokens->isCurrent(self::TOK_IDENTIFIER, self::TOK_STRING)) {
							$right[] = self::sanitizeArgumentExpression($tokens->currentValue(), $token);

						} elseif (!$tokens->isCurrent(',', self::TOK_WHITESPACE)) {
							throw new ParserException('Unexpected token ' . $token->type);
						}
					}

					if (empty($right)) {
						throw new ParserException('Argument for ' . $comparator . ' cannot be an empty array.');
					}

					$conditions[] = [$left, $comparator, $right];

					continue;
				}
			}

			$tokens->nextToken();
			$right = self::sanitizeArgumentExpression(self::nextValue(
				$tokens,
				[self::TOK_IDENTIFIER, self::TOK_STRING],
				self::TOK_WHITESPACE
			), $tokens->currentToken());

			$conditions[] = [$left, $comparator, $right];
		}

		if (!$conditions) {
			if ($tokens->isCurrent(')')) {
				$tokens->nextToken();
			}

			return null;
		}

		try {
			if ($operator === ',') {
				$operator = Matcher\Criteria::TYPE_AND;
			}

			$criteria = new Matcher\Criteria($operator ? : Matcher\Criteria::TYPE_AND);
			foreach ($conditions as $condition) {
				if ($condition instanceof Matcher\Criteria) {
					$criteria->where($condition);

				} else {
					$criteria->where($condition[0], $condition[1], $condition[2]);
				}
			}
		} catch (InvalidArgumentException $e) {
			throw new ParserException('Invalid arguments', 0, $e);
		}

		if ($tokens->isCurrent(')')) {
			$tokens->nextToken();
		}

		return $criteria;
	}

}
