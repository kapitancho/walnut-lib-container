<?php

namespace Walnut\Lib\Container;

use Closure;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface {

	/**
	 * @var bool[]
	 */
	private array $cycleProtector = [];

	/**
	 * @var array<class-string, object>
	 */
	private array $containerCache = [];

	private /*readonly*/ ContainerMapper $containerMapper;
	private /*readonly*/ ContainerLoader $containerLoader;

	/**
	 * DI constructor.
	 * @param array<string, string|callable|array<string, mixed>> $mapping
	 */
	public function __construct(array $mapping) {
		$this->containerCache[ContainerInterface::class] = $this->containerCache[self::class] = $this;
		$this->containerMapper = new ContainerMapper($mapping);
		$this->containerLoader = new ContainerLoader(new ParameterBuilder($this));
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return T
	 */
	private function instantiate(string $className): object {
		if ($this->cycleProtector[$className] ?? false) {
			throw new ContainerException($className, "Class $className has a cyclic dependency");
		}
		$this->cycleProtector[$className] = true;
		$containerMapping = $this->containerMapper->findClass($className);

		if ($containerMapping instanceof Closure) {
			return $this->containerLoader->loadUsingClosure($containerMapping, $className);
		}
		return $this->containerLoader->loadUsingMapping($containerMapping, $className);
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
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return object Entry.
	 */
	public function get($id): object {
		/**
		 * @var class-string $id
		 */
		return $this->containerCache[$id] ??= $this->instantiate($id);
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has($id): bool {
		return array_key_exists($id, $this->containerCache) ||
			class_exists($id) || interface_exists($id);
	}
	
}