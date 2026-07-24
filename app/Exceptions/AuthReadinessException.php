<?php

namespace App\Exceptions;

use RuntimeException;

class AuthReadinessException extends RuntimeException
{
    public function __construct(
        public readonly string $check,
        public readonly string $reason,
    ) {
        parent::__construct("{$check}:{$reason}");
    }
}
