<?php

namespace App\Auth;

class DirectoryAuthorizationResult
{
    public function __construct(
        public readonly bool $eligible,
        public readonly ?string $protectedRole,
        public readonly ?string $previousProtectedRole,
        public readonly bool $authorizationChanged,
    ) {}
}
