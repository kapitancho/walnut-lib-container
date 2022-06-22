<?php

use PHPUnit\Framework\TestCase;
use Walnut\Lib\Container\Container;
use Walnut\Lib\Container\ContainerAdapter;
use Walnut\Lib\Container\ContainerDecorator;
use Walnut\Lib\Container\ContainerException;
use Walnut\Lib\Container\FromAttribute;
use Walnut\Lib\Container\NotFoundException;

interface MockContainerInterface {}
abstract class MockContainerAbstractClass implements MockContainerInterface {}
final class MockContainerConcreteClass extends MockContainerAbstractClass {}
final class MockContainerCustomClass {
	public function __construct(
		public int $number,
		public MockContainerInterface $obj
	) {}
}
final class MockContainerParameterClass {
	public function __construct(
		public int $param,
		public ?string $nullableText,
		public MockContainerCustomClass $obj,
		public MockContainerInterfaceOther|MockContainerInterface $union,
		public int $defaultValue = 1,
		public ?int $nullable = null,
	) {}
}

final class IntegrationTest extends TestCase {

	public function testOk(): void {
		$mapping = [
			MockContainerInterface::class => MockContainerAbstractClass::class,
			MockContainerAbstractClass::class => MockContainerConcreteClass::class,
			MockContainerCustomClass::class => static fn(
				MockContainerInterface $obj
			) =>
				new MockContainerCustomClass(strlen(get_class($obj)), $obj),
			MockContainerParameterClass::class => [
				'param' => 10
			]
		];
		$container = new ContainerAdapter($c = new Container($mapping));
		$this->assertInstanceOf(MockContainerConcreteClass::class,
			$container->get(MockContainerInterface::class));
		$this->assertEquals(strlen(MockContainerConcreteClass::class),
			$container->get(MockContainerCustomClass::class)->number);
		$this->assertEquals(10, $container->get(MockContainerParameterClass::class)->param);
		$this->assertEquals(1, $container->get(MockContainerParameterClass::class)->defaultValue);
		$this->assertNull($container->get(MockContainerParameterClass::class)->nullable);

		$this->assertEquals(
			$container->get(MockContainerInterface::class),
			$c->instanceOf(MockContainerInterface::class)
		);
		$this->assertTrue($container->has(MockContainerInterface::class));
		$this->assertFalse($container->has(MockContainerInterface::class . 'INVALID NAME'));
	}

	public function testDecorator(): void {
		$container = new ContainerAdapter(new Container([], (
			new class implements ContainerDecorator {
				public function apply(object $instance, string $className): object {
					return $instance;
				}
			}
		)::class));
		$this->assertFalse($container->has(MockContainerInterface::class . 'INVALID NAME'));
	}

	public function testDirectCycle(): void {
		$this->expectException(ContainerException::class);
		$mapping = [
			MockContainerInterface::class => MockContainerAbstractClass::class,
			MockContainerAbstractClass::class => MockContainerInterface::class
		];
		$container = new ContainerAdapter(new Container($mapping));
		$container->get(MockContainerInterface::class);
	}

	public function testContainerException(): void {
		$className = MockContainerInterface::class . 'INVALID NAME';
		$container = new ContainerAdapter(new Container([]));
		try {
			$container->get($className);
		} catch (ContainerException $ex) {
			$this->assertEquals($className, $ex->getId());
		}
	}

	public function testInstantiateWrongType(): void {
		$this->expectException(ContainerException::class);
		$mapping = [
			MockContainerInterface::class => MockContainerAbstractClass::class,
			MockContainerAbstractClass::class => MockContainerConcreteClass::class,
			MockContainerParameterClass::class => static fn(MockContainerInterface $obj) =>
				new MockContainerCustomClass(strlen(get_class($obj)), $obj)
		];
		$container = new ContainerAdapter(new Container($mapping));
		$container->get(MockContainerParameterClass::class);
	}

	public function testInstantiateInterface(): void {
		$this->expectException(ContainerException::class);
		$container = new ContainerAdapter(new Container([]));
		$container->get(MockContainerInterface::class);
	}

	public function testInstantiateAbstractClass(): void {
		$this->expectException(ContainerException::class);
		$container = new ContainerAdapter(new Container([
			MockContainerInterface::class => MockContainerAbstractClass::class
		]));
		$container->get(MockContainerInterface::class);
	}

	public function testIndirectCycle(): void {
		$this->expectException(ContainerException::class);
		$mapping = [
			MockContainerInterface::class => MockContainerAbstractClass::class,
			MockContainerAbstractClass::class => static fn(MockContainerInterface $param) => null
		];
		$container = new ContainerAdapter(new Container($mapping));
		$container->get(MockContainerInterface::class);
	}

	public function testUnknownClass(): void {
		$this->expectException(NotFoundException::class);
		$container = new ContainerAdapter(new Container([]));
		$container->get(MockContainerInterface::class . 'INVALID NAME');
	}

	public function testMissingParameter(): void {
		$this->expectException(ContainerException::class);

		$mapping = [];
		$container = new ContainerAdapter(new Container($mapping));
		$container->get(MockContainerParameterClass::class);
	}

}