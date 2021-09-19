<?php

use PHPUnit\Framework\TestCase;
use Walnut\Lib\Container\Container;
use Walnut\Lib\Container\ContainerAdapter;
use Walnut\Lib\Container\ContainerException;
use Walnut\Lib\Container\FromAttribute;
use Walnut\Lib\Container\NotFoundException;

#[Attribute]
final class MockContainerAttribute {
	public function __construct(public int $value) {}
}

final class MockContextTestTarget1 {
	public function __construct(public int $value) {}
}
final class MockContextTestTarget2 {
	public function __construct(public int $value) {}
}
final class MockContextTestTarget3 {
	public function __construct(public int $value) {}
}

final class MockContextTestSource {
	public function __construct(
		#[MockContainerAttribute(1)] public MockContextTestTarget1 $a,
		#[MockContainerAttribute(2)] public MockContextTestTarget2 $b,
		#[MockContainerAttribute(3)] public MockContextTestTarget3 $c,
		public MockContextTestTarget2 $d,
		public MockContextTestTarget3 $e
	) {}
}

final class MockContextTestErrorSource {
	public function __construct(
		public MockContextTestTarget1 $a
	) {}
}

final class ContextTest extends TestCase {

	public function testOk(): void {
		$mapping = [
			MockContextTestTarget1::class => static fn(
				#[FromAttribute(MockContainerAttribute::class)] MockContainerAttribute $attribute
			) => new MockContextTestTarget1($attribute->value),
			MockContextTestTarget2::class => static fn(
				#[FromAttribute(MockContainerAttribute::class)] ?MockContainerAttribute $attribute
			) => new MockContextTestTarget2($attribute->value ?? -1),
			MockContextTestTarget3::class => static fn(
				#[FromAttribute(MockContainerAttribute::class)] array $attribute
			) => new MockContextTestTarget3($attribute[0]->value ?? -2),
		];
		$container = new ContainerAdapter($c = new Container($mapping));

		$source = $c->instanceOf(MockContextTestSource::class);
		$this->assertEquals(1, $source->a->value);
		$this->assertEquals(2, $source->b->value);
		$this->assertEquals(3, $source->c->value);
		$this->assertEquals(-1, $source->d->value);
		$this->assertEquals(-2, $source->e->value);

		$this->expectException(ContainerException::class);
		$c->instanceOf(MockContextTestErrorSource::class);
	}

}