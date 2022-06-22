<?php

namespace Walnut\Lib\Container;

use Attribute;
use Closure;
use Psr\Container\ContainerInterface;

final class Container {

	/**
	 * @var bool[]
	 */
	private array $cycleProtector = [];

	/**
	 * @var array<class-string, object>
	 */
	private array $containerCache = [];

	private readonly ContainerMapper $containerMapper;
	private readonly ContainerLoader $containerLoader;

	/**
	 * DI constructor.
	 * @param array<string, string|callable|array<string, mixed>> $mapping
	 */
	public function __construct(array $mapping, 
	            private readonly ContainerDecorator $containerDecorator = new EmptyContainerDecorator) {
		$this->containerCache[self::class] = $this;
		$this->containerCache[ContainerInterface::class] = new ContainerAdapter($this);
		$this->containerMapper = new ContainerMapper($mapping);
		$this->containerLoader = new ContainerLoader(new ParameterBuilder($this));
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param array<class-string<Attribute>, Attribute[]> $context
	 * @return T
	 */
	private function instantiate(string $className, array $context = []): object {
		if ($this->cycleProtector[$className] ?? false) {
			throw new ContainerException($className, "Class $className has a cyclic dependency: " .
				implode(' / ', array_keys($this->cycleProtector)));
		}
		$this->cycleProtector[$className] = true;
		$containerMapping = $this->containerMapper->findClass($className);

		$result = ($containerMapping instanceof Closure) ?
			$this->containerLoader->loadUsingClosure($containerMapping, $className, $context) :
			$this->containerLoader->loadUsingMapping($containerMapping, $className);

		$result = $this->containerDecorator->apply($result, $className);
		
		unset($this->cycleProtector[$className]);
		return $result;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param array<class-string<Attribute>, Attribute[]> $context
	 * @return T
	 */
	public function contextInstanceOf(string $className, array $context = []): object {
		return count($context) ? $this->instantiate($className, $context) :
			$this->instanceOf($className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function instanceOf(string $className): object {
		/**
		 * @var T
		 */
		return $this->containerCache[$className] ??= $this->instantiate($className);
	}

	/**
	 * @param class-string $className
	 * @return bool
	 */
	public function has(string $className): bool {
		return array_key_exists($className, $this->containerCache) ||
			interface_exists($className) ||
			class_exists($className) ;
	}
	
}