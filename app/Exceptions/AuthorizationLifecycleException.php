<?php

namespace App\Exceptions;

use RuntimeException;

class AuthorizationLifecycleException extends RuntimeException
{
    public function __construct(
        public readonly string $check,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($reason === null ? $check : "{$check}/{$reason}");
    }

    public function label(): string
    {
        return $this->reason === null
            ? $this->check
            : "{$this->check}/{$this->reason}";
    }
}
