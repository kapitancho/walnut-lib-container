<?php

namespace Walnut\Lib\Container;

use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ContainerException extends \RuntimeException implements ContainerExceptionInterface {

	/**
	 * ContainerException constructor.
	 * @param string $id
	 * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
	 * @param ?Throwable $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct(private readonly string $id, $message = "", $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function getId(): string {
		return $this->id;
	}


}