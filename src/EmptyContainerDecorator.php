<?php

namespace Walnut\Lib\Container;

use Closure;

final class EmptyContainerDecorator implements ContainerDecorator {

	/**
	 * @template T of object
	 * @param T $instance
	 * @param class-string<T> $className
	 * @return T
	 */
	public function apply(object $instance, string $className): object {
		return $instance;
	}
}