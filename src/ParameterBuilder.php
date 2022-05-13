<?php

namespace Walnut\Lib\Container;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final class ParameterBuilder {
	public function __construct(
		private readonly Container $container
	) {}

	/**
	 * @param ReflectionParameter $parameter
	 * @return array<class-string<\Attribute>, \Attribute[]>
	 */
	private function getContext(ReflectionParameter $parameter): array {
		$result = [];
		foreach ($parameter->getAttributes() as $attribute) {
			$result[$attribute->getName()] ??= [];
			$result[$attribute->getName()][] = $attribute->newInstance();
		}
		/**
		 * @var array<class-string<\Attribute>, \Attribute[]>
		 */
		return $result;
	}

	private function getParameterValue(ReflectionFunctionAbstract $method, ReflectionParameter $parameter): mixed {
		$type = $parameter->getType();
		if ($type instanceof ReflectionUnionType) {
			foreach($type->getTypes() as $subType) {
				if (!$subType->isBuiltin()) {
					/**
					 * @var class-string
					 */
					$subTypeName = $subType->getName();
					if ($this->container->has($subTypeName)) {
						return $this->container->contextInstanceOf($subTypeName,
							$this->getContext($parameter));
					}
				}
			}
		}
		if (($type instanceof ReflectionNamedType) && !$type->isBuiltin()) {
			/**
			 * @var class-string
			 */
			$typeName = $type->getName();
			return $this->container->contextInstanceOf($typeName, $this->getContext($parameter));
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
	 * @param ?ReflectionMethod $extraSource
	 * @param array<class-string<\Attribute>, \Attribute[]> $context
	 * @return list<mixed>
	 */
	public function getMethodArgs(
		ReflectionFunctionAbstract $method,
		array $extraMapping = [],
		array $context = []
	): array {
		/**
		 * @var list<mixed> $args
		 */
		$args = [];
		foreach($method->getParameters() as $parameter) {
			if ($fromAttribute = $parameter->getAttributes(FromAttribute::class)[0] ?? null) {
				/**
				 * @var FromAttribute
				 */
				$fromAttributeInstance = $fromAttribute->newInstance();
				$attributeName = $fromAttributeInstance->attributeName;
				$parameterType = $parameter->getType();
				if ($parameterType instanceof ReflectionNamedType && $parameterType->getName() === 'array') {
					$args[] = $context[$attributeName] ?? [];
				} else {
					$args[] = $context[$attributeName][0] ?? (
						$parameter->allowsNull() ? null :
						throw new ContainerException($attributeName, "Expected attribute $attributeName not found!")
					);
				}
			} else {
				/**
				 * @var mixed
				 */
				$args[] = array_key_exists($key = $parameter->getName(), $extraMapping) ?
					$extraMapping[$key] : $this->getParameterValue($method, $parameter);
			}
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