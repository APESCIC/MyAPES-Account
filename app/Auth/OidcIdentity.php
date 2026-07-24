<?php

namespace App\Auth;

final readonly class OidcIdentity
{
    public function __construct(
        public ?string $subject,
        public ?string $email,
        public ?string $name,
    ) {}
}
