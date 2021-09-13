<?php

namespace Walnut\Lib\Container;

use Closure;
use Psr\Container\{ContainerInterface};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

/**
 * @template T
 */
final class ContainerMapping {

	/**
	 * @param class-string<T> $className
	 * @param array $additionalContext
	 */
	public function __construct(
		public /*readonly*/ string $className,
		public /*readonly*/ array $additionalContext
	) {}

}