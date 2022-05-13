<?php

namespace Walnut\Lib\Container;

use Closure;
use Psr\Container\ContainerInterface;

final class ContainerAdapter implements ContainerInterface {

	public function __construct(private readonly Container $container) {}

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
		return $this->container->instanceOf($id);
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
		/**
		 * @var class-string $id
		 */
		return $this->container->has($id);
	}
	
}