<?php

namespace App\Exceptions;

use RuntimeException;

class ModuleLifecycleException extends RuntimeException
{
    /** @param array<string, int|string|null> $context */
    public function __construct(
        public readonly string $reason,
        string $message = 'Module lifecycle validation failed.',
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }
}
