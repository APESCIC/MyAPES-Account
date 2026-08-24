<?php

namespace App\Auth;

final readonly class DirectoryUserProfile
{
    /**
     * @param  array<int, string>  $groups
     */
    public function __construct(
        public string $email,
        public string $name,
        public ?string $jobTitle,
        public ?string $workPhone,
        public array $groups,
    ) {}
}
