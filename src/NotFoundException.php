<?php

namespace Walnut\Lib\Container;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}