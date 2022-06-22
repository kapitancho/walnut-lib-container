<?php

namespace Walnut\Lib\Container;

interface ContainerDecorator {

	/**
	 * @template T of object
	 * @param T $instance
	 * @param class-string<T> $className
	 * @return T
	 */
	public function apply(object $instance, string $className): object;
}