<?php

namespace Walnut\Lib\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

final class ContainerLoader {

	public function __construct(
		private /*readonly*/ ParameterBuilder $parameterBuilder
	) {}

	/**
	 * @template T
	 * @param Closure $closure
	 * @param class-string<T> $className
	 * @param array<class-string<\Attribute>, \Attribute[]> $context
	 * @return T
	 * @throws ContainerException
	 */
	public function loadUsingClosure(Closure $closure, string $className, array $context): object {
		try {
			$result = $closure(... $this->parameterBuilder->getMethodArgs(
				new ReflectionFunction($closure), [
					'className' => $className,
				], $context
			));
			if (!($result instanceof $className)) {
				throw new ContainerException($className, "The callable did not return an instance of $className");
			}
			return $result;
		} catch (ReflectionException $ex) {
			// @codeCoverageIgnoreStart
			throw new ContainerException($className, $ex->getMessage());
			// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * @template T of object
	 * @param ContainerMapping<T> $containerMapping
	 * @param class-string $className
	 * @return T
	 * @throws ContainerException
	 */
	public function loadUsingMapping(ContainerMapping $containerMapping, string $className): object {
		try {
			$additionalContext = $containerMapping->additionalContext;
			$currentClassName = $containerMapping->className;

			if (!class_exists($currentClassName) && !interface_exists($currentClassName)) {
				throw new NotFoundException($className, "Class $currentClassName does not exist");
			}
			$classInfo = new ReflectionClass($currentClassName);
			if ($classInfo->isInterface()) {
				throw new ContainerException($className, "Interface $className cannot be instantiated");
			}
			if ($classInfo->isAbstract()) {
				throw new ContainerException($className, "Abstract class $className cannot be instantiated");
			}
			/**
			 * @var T
			 */
			return $classInfo->newInstanceArgs($this->parameterBuilder->getClassArgs($classInfo, $additionalContext));
		} catch (ReflectionException $ex) {
			// @codeCoverageIgnoreStart
			throw new ContainerException($className, $ex->getMessage());
			// @codeCoverageIgnoreEnd
		}
	}
	
}