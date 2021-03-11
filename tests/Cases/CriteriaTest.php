<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Aop\Pointcut\Matcher\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\PhpLiteral;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;

class CriteriaTest extends TestCase
{

	public function testEqual(): void
	{
		$this->assertTrue(Criteria::compare(true, Criteria::EQ, true));
		$this->assertFalse(Criteria::compare(true, Criteria::EQ, false));
		$this->assertFalse(Criteria::compare(true, Criteria::NEQ, true));
		$this->assertTrue(Criteria::compare(true, Criteria::NEQ, false));
	}
	public function testGreater(): void
	{
		$this->assertTrue(Criteria::compare(2, Criteria::GT, 1));
		$this->assertFalse(Criteria::compare(1, Criteria::GT, 1));
		$this->assertFalse(Criteria::compare(1, Criteria::GT, 2));
	}
	public function testGreaterOrEqual(): void
	{
		$this->assertTrue(Criteria::compare(2, Criteria::GTE, 1));
		$this->assertTrue(Criteria::compare(1, Criteria::GTE, 1));
		$this->assertFalse(Criteria::compare(1, Criteria::GTE, 2));
	}
	public function testLower(): void
	{
		$this->assertTrue(Criteria::compare(1, Criteria::LT, 2));
		$this->assertFalse(Criteria::compare(2, Criteria::LT, 2));
		$this->assertFalse(Criteria::compare(2, Criteria::LT, 1));
	}
	public function testLowerOrEqual(): void
	{
		$this->assertTrue(Criteria::compare(1, Criteria::LTE, 2));
		$this->assertTrue(Criteria::compare(2, Criteria::LTE, 2));
		$this->assertFalse(Criteria::compare(2, Criteria::LTE, 1));
	}
	public function testIs(): void
	{
		$foo = new stdClass();
		$bar = new stdClass();
		$this->assertTrue(Criteria::compare($foo, Criteria::IS, $foo));
		$this->assertFalse(Criteria::compare($foo, Criteria::IS, $bar));
		$this->assertTrue(Criteria::compare($bar, Criteria::IS, $bar));
		$this->assertFalse(Criteria::compare($bar, Criteria::IS, $foo));
	}
	public function testIn(): void
	{
		$dave = new stdClass();
		$lister = new ArrayCollection([$dave]);
		$kryten = new SplObjectStorage();
		$kryten->attach($dave);
		$cat = new stdClass();
		$this->assertTrue(Criteria::compare($dave, Criteria::IN, $lister));
		$this->assertFalse(Criteria::compare($dave, Criteria::NIN, $lister));
		$this->assertTrue(Criteria::compare($dave, Criteria::IN, $kryten));
		$this->assertFalse(Criteria::compare($dave, Criteria::NIN, $kryten));
		$this->assertFalse(Criteria::compare($cat, Criteria::IN, $lister));
		$this->assertTrue(Criteria::compare($cat, Criteria::NIN, $lister));
		$this->assertFalse(Criteria::compare($cat, Criteria::IN, $kryten));
		$this->assertTrue(Criteria::compare($cat, Criteria::NIN, $kryten));
	}

	public function testIn_WithWrongDataType_ShouldThrowException(): void
	{
		$this->expectExceptionMessage('Right value is expected to be array or instance of Traversable');
		Criteria::compare(new stdClass(), Criteria::IN, new stdClass());
	}

	public function testContains(): void
	{
		$dave = new stdClass();
		$lister = new ArrayCollection([$dave]);
		$kryten = new SplObjectStorage();
		$kryten->attach($dave);
		$cat = new stdClass();
		$this->assertTrue(Criteria::compare($lister, Criteria::CONTAINS, $dave));
		$this->assertTrue(Criteria::compare($kryten, Criteria::CONTAINS, $dave));
		$this->assertFalse(Criteria::compare($lister, Criteria::CONTAINS, $cat));
		$this->assertFalse(Criteria::compare($kryten, Criteria::CONTAINS, $cat));
	}

	public function testContains_WithWrongDataType_ShouldThrowException(): void
	{
		$this->expectExceptionMessage('Right value is expected to be array or instance of Traversable');
		Criteria::compare(new stdClass(), Criteria::CONTAINS, new stdClass());
	}
	public function testMatches(): void
	{
		$dave = ['a', 'b', 'c'];
		$cat = ['c', 'd', 'e'];
		$lister = ['e', 'f', 'g'];
		$this->assertTrue(Criteria::compare($dave, Criteria::MATCHES, $cat));
		$this->assertTrue(Criteria::compare($cat, Criteria::MATCHES, $lister));
		$this->assertFalse(Criteria::compare($lister, Criteria::MATCHES, $dave));
	}

	public function testMatches_WithWrongRightDataType_ShouldThrowException(): void
	{
		$this->expectExceptionMessage('Right value is expected to be array or Traversable');
		Criteria::compare(['a', 'b', 'c'], Criteria::MATCHES, 'h');
	}

	public function testMatches_WithWrongLeftDataType_ShouldThrowException(): void
	{
		$this->expectExceptionMessage('Left value is expected to be array or Traversable');
		Criteria::compare('h', Criteria::MATCHES, ['a', 'b', 'c']);
	}

	public function testSerialize_propertyAccess(): void
	{
		$criteria = Criteria::create()->where('this.foo.bar', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertSame("(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this, 'foo.bar'), '==', true))", (string) $criteria->serialize(new ContainerBuilder()));
	}
	public function testSerialize_arguments(): void
	{
		$criteria = Criteria::create()->where('$arg', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertSame("(Criteria::compare(\$arg, '==', true))", (string) $criteria->serialize(new ContainerBuilder()));
	}
	public function testSerialize_parameter(): void
	{
		$builder = new ContainerBuilder();
		$builder->parameters['foo']['bar'] = 'complicated value!';
		$criteria = Criteria::create()->where('%foo.bar%', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertSame("(Criteria::compare('complicated value!', '==', true))", (string) $criteria->serialize($builder));
	}
	public function testSerialize_service_byName(): void
	{
		$criteria = Criteria::create()->where('context.foo.bar', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertSame("(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_contributte_aopContainer->getService('foo'), 'bar'), '==', true))", (string) $criteria->serialize(new ContainerBuilder()));
	}
	public function testSerialize_service_byType(): void
	{
		$criteria = Criteria::create()->where('context.stdClass.bar', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertStringMatchesFormat("(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_contributte_aopContainer->getByType('stdClass'), 'bar'), '==', true))", (string) $criteria->serialize(new ContainerBuilder()));
		$criteria = Criteria::create()->where('context.Tests\Cases\CriteriaTest.bar', Criteria::EQ, new PhpLiteral('TRUE'));
		$this->assertStringMatchesFormat("(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_contributte_aopContainer->getByType('Tests\\Cases\\CriteriaTest'), 'bar'), '==', true))", (string) $criteria->serialize(new ContainerBuilder()));
	}

}
