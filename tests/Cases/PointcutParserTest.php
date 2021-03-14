<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Aop\Pointcut;
use Contributte\Aop\Pointcut\Filter;
use Contributte\Aop\Pointcut\Matcher\Criteria;
use Contributte\Aop\Pointcut\Parser;
use Nette;
use Nette\PhpGenerator\PhpLiteral;
use PHPUnit\Framework\TestCase;
use Tests\Files\Pointcut\CommonClass;
use Tests\Files\Pointcut\FeedAggregator;
use Tests\Files\Pointcut\LoggerInterface;
use Tests\Files\Pointcut\MyPointcutFilter;
use Tests\Files\Pointcut\PackageClass;
use Tests\Files\Pointcut\PointcutTestingAspect;

class PointcutParserTest extends TestCase
{

	private ?Pointcut\MatcherFactory $matcherFactory = null;

	public function getMatcherFactory(): Pointcut\MatcherFactory
	{
		if ($this->matcherFactory === null) {
			$this->matcherFactory = new Pointcut\MatcherFactory(new Nette\DI\ContainerBuilder());
		}

		return $this->matcherFactory;
	}



	protected function tearDown(): void
	{
		$this->matcherFactory = null;
	}


	/**
	 * @return array<string|int, array<Pointcut\Rules|string>>
	 */
	public function dataParse(): array
	{
		$mf = $this->getMatcherFactory();

		$data = [];

		$data['Exact method'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', CommonClass::class),
				$mf->getMatcher('method', 'deletePost'),
			]),
			'method(Tests\Files\Pointcut\CommonClass->deletePost())',
		];

		$data['public method name'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', CommonClass::class),
				$mf->getMatcher('method', 'public methodName'),
			]),
			'method(public Tests\Files\Pointcut\CommonClass->methodName())',
		];

		$data['protected method name'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', CommonClass::class),
				$mf->getMatcher('method', 'protected methodName'),
			]),
			'method(protected Tests\Files\Pointcut\CommonClass->methodName())',
		];

		$data['commonclass->*'] = [
			$mf->getMatcher('class', CommonClass::class),
			'method(Tests\Files\Pointcut\CommonClass->*())',
		];

		$data['commonclass->public *'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', CommonClass::class),
				$mf->getMatcher('method', 'public *'),
			]),
			'method(public Tests\Files\Pointcut\CommonClass->*())',
		];

		$data['Example\MyPackage*->method delete*'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\MyPackage*'),
				$mf->getMatcher('method', 'delete*'),
			]),
			'method(Example\MyPackage*->delete*())',
		];

		$data['method delete*'] = [
			$mf->getMatcher('method', 'delete*'),
			'method(*->delete*())',
		];

		$data['commonclass->*[!inject]*'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', CommonClass::class),
				$mf->getMatcher('method', '[!inject]*'),
			]),
			'method(Tests\Files\Pointcut\CommonClass->[!inject]*())',
		];

		$data['PackageClass->update(title="Contributte")'] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', PackageClass::class),
				$mf->getMatcher('method', 'update'),
				$mf->getMatcher('arguments', Criteria::create()
					->where('title', Criteria::EQ, new PhpLiteral('"Contributte"'))
					->where('override', Criteria::EQ, new PhpLiteral('TRUE'))),
			]),
			'method(Tests\Files\Pointcut\PackageClass->update(title == "Contributte", override == TRUE))',
		];

		$data['CommonClass'] = [
			$mf->getMatcher('class', CommonClass::class),
			'class(\Tests\Files\Pointcut\CommonClass)',
		];

		$data['Class wildcard'] = [
			$mf->getMatcher('class', 'Example\MyPackage\Service\*'),
			'class(Example\MyPackage\Service\*)',
		];

		$data['within LoggerInterface'] = [
			$mf->getMatcher('within', LoggerInterface::class),
			'within(Tests\Files\Pointcut\LoggerInterface)',
		];

		$data[] = [
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral('TRUE'))),
			'setting(my.configuration.option)',
		];

		$data[] = [
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral("'AOP is cool'"))),
			"setting(my.configuration.option == 'AOP is cool')",
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.name', Criteria::EQ, new PhpLiteral('"Andi"'))),
			'evaluate(current.securityContext.party.name == "Andi")',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someObject.someProperty', Criteria::EQ, 'current.securityContext.party.name')),
			'evaluate(this.someObject.someProperty == current.securityContext.party.name)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someProperty', Criteria::IN, [
				new PhpLiteral('TRUE'),
				new PhpLiteral('"someString"'),
				'current.securityContext.party.address',
			])),
			'evaluate(this.someProperty in (TRUE, "someString", current.securityContext.party.address))',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::CONTAINS, 'this.myAccount')),
			'evaluate(current.securityContext.party.accounts contains this.myAccount)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, [
				new PhpLiteral("'Administrator'"),
				new PhpLiteral("'Customer'"),
				new PhpLiteral("'User'"),
			])),
			"evaluate(current.securityContext.party.accounts matches ('Administrator', 'Customer', 'User'))",
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, 'this.accounts')),
			'evaluate(current.securityContext.party.accounts matches this.accounts)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('%foo.dave%', Criteria::EQ, new PhpLiteral('TRUE'))),
			'evaluate(%foo.dave%)',
		];

		$data[] = [
			$mf->getMatcher('filter', MyPointcutFilter::class),
			'filter(Tests\Files\Pointcut\MyPointcutFilter)', // implements \Contributte\Aop\Pointcut\Rule
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\TestPackage\PointcutTestingTargetClass*'),
				new Pointcut\Matcher\Inverse($mf->getMatcher('class', PackageClass::class)),
			]),
			'method(Example\TestPackage\PointcutTestingTargetClass*->*()) && !method(Tests\Files\Pointcut\PackageClass->*())',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					$mf->getMatcher('class', PointcutTestingAspect::class),
					$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', PointcutTestingAspect::class),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				]),
			], Pointcut\Rules::OP_OR),
			'Tests\Files\Pointcut\PointcutTestingAspect->pointcutTestingTargetClasses || Tests\Files\Pointcut\PointcutTestingAspect->otherPointcutTestingTargetClass',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					new Pointcut\Rules([
						$mf->getMatcher('class', PointcutTestingAspect::class),
						$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
					]),
					$mf->getMatcher('within', LoggerInterface::class),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', PointcutTestingAspect::class),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				]),
			], Pointcut\Rules::OP_OR),
			'(Tests\Files\Pointcut\PointcutTestingAspect->pointcutTestingTargetClasses && within(Tests\Files\Pointcut\LoggerInterface))' . // intentionally no space after )
				'|| Tests\Files\Pointcut\PointcutTestingAspect->otherPointcutTestingTargetClass',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\TestPackage\Basic*'),
				$mf->getMatcher('within', 'Contributte\Service*'),
			], Pointcut\Rules::OP_OR),
			'method(Example\TestPackage\Basic*->*()) || within(Contributte\Service*)',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					$mf->getMatcher('class', FeedAggregator::class),
					$mf->getMatcher('method', 'public [import|update]*'),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', PointcutTestingAspect::class),
					$mf->getMatcher('method', 'someOtherPointcut'),
				]),
			], Pointcut\Rules::OP_OR),
			'method(public Tests\Files\Pointcut\FeedAggregator->[import|update]*()) || Tests\Files\Pointcut\PointcutTestingAspect->someOtherPointcut',
		];

		return $data;
	}



	/**
	 * @dataProvider dataParse
	 */
	public function testParse(Filter $expected, string $input): void
	{
		$parser = new Parser($this->getMatcherFactory());
		$this->assertEquals($expected, $parser->parse($input));
	}

	public function testParseClassAttributedWith(): void
	{
		$mf = $this->getMatcherFactory();
		$parser = new Parser($this->getMatcherFactory());
		$this->assertEquals(
			$mf->getMatcher('classAttributedWith', 'Doctrine\ORM\Mapping\Entity'),
			$parser->parse('classAttributedWith(Doctrine\ORM\Mapping\Entity)')
		);
	}

	public function testParseMethodAttributedWith(): void
	{
		$mf = $this->getMatcherFactory();
		$parser = new Parser($this->getMatcherFactory());
		$this->assertEquals(
			$mf->getMatcher('classAttributedWith', 'Acme\Demo\Annotations\Special'),
			$parser->parse('classAttributedWith(Acme\Demo\Annotations\Special)')
		);
	}

}
