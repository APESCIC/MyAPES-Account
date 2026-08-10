<?php

namespace App\Exceptions;

use RuntimeException;

class MaintenanceTransitionException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct($reasonCode);
    }
}
