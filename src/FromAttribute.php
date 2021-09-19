<?php

namespace Walnut\Lib\Container;

#[\Attribute]
final class FromAttribute {
	public function __construct(public /*readonly*/ string $attributeName) {}
}