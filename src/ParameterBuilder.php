<?php

namespace Walnut\Lib\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final class ParameterBuilder {
	public function __construct(
		private /*readonly*/ ContainerInterface $container
	) {}

	private function getParameterValue(ReflectionFunctionAbstract $method, ReflectionParameter $parameter): mixed {
		$type = $parameter->getType();
		if ($type instanceof ReflectionUnionType) {
			foreach($type->getTypes() as $subType) {
				if (!$subType->isBuiltin() && $this->container->has($name = $subType->getName())) {
					return $this->container->get($name);
				}
			}
		}
		if (($type instanceof ReflectionNamedType) && !$type->isBuiltin()) {
			return $this->container->get($type->getName());
		}
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}
		if ($type && $type->allowsNull()) {
			return null;
		}
		$className = ($method instanceof ReflectionMethod) ? $method->getDeclaringClass()->getName() : '';
		$methodName = $method->getName();
		$key = $parameter->getName();
		throw new ContainerException($className, "Method $className::$methodName cannot be instantiated due to missing [$key] parameter");
	}

	/**
	 * @param ReflectionFunctionAbstract $method
	 * @param array $extraMapping
	 * @return list<mixed>
	 */
	public function getMethodArgs(ReflectionFunctionAbstract $method, array $extraMapping = []): array {
		/**
		 * @var list<mixed> $args
		 */
		$args = [];
		foreach($method->getParameters() as $parameter) {
			/**
			 * @var mixed
			 */
			$args[] = array_key_exists($key = $parameter->getName(), $extraMapping) ?
				$extraMapping[$key] : $this->getParameterValue($method, $parameter);
		}
		return $args;
	}

	/**
	 * @param ReflectionClass $class
	 * @param array $extraMapping
	 * @return list<mixed>
	 */
	public function getClassArgs(ReflectionClass $class, array $extraMapping = []): array {
		$constructor = $class->getConstructor();
		if (!$constructor) {
			return [];
		}
		return $this->getMethodArgs($constructor, $extraMapping);
	}

}