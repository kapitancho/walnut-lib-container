<?php

namespace Walnut\Lib\Container;

use Closure;

final class ContainerMapper {

	/**
	 * DI constructor.
	 * @param array<string, string|callable|array<string, mixed>> $mapping
	 */
	public function __construct(
		private /*readonly*/ array $mapping
	) {}

	/**
	 * @template T of object
	 * @param class-string<T> $key
	 * @return ContainerMapping<T>|Closure
	 */
	public function findClass(string $key): ContainerMapping|Closure {
		$additionalContext = [];
		$currentClassName = $key;
		$cycleProtector = [$key => true];

		do {
			$redo = false;
			$mappingInfo = $this->mapping[$currentClassName] ?? null;

			if (is_callable($mappingInfo)) {
				/**
				 * @psalm-suppress MixedArgumentTypeCoercion
				 */
				return Closure::fromCallable($mappingInfo);
			}
			if (is_array($mappingInfo)) {
				$additionalContext = $mappingInfo;
			}
			if (is_string($mappingInfo)) {
				if ($cycleProtector[$mappingInfo] ?? null) {
					throw new ContainerException($key, "Class $key has a cyclic dependency");
				}
				/**
				 * @var class-string<T> $currentClassName
				 */
				$currentClassName = $mappingInfo;
				$cycleProtector[$currentClassName] = true;
				$redo = true;
			}
		} while($redo);
		/**
		 * @var ContainerMapping<T>
		 */
		return new ContainerMapping($currentClassName, $additionalContext);
	}

}