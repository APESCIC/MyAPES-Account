<?php

namespace App\Exceptions;

use RuntimeException;

class AuthorizationLifecycleException extends RuntimeException
{
    public function __construct(
        public readonly string $check,
    ) {
        parent::__construct($check);
    }
}
