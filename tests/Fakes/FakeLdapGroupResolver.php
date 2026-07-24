<?php

namespace Tests\Fakes;

use App\Services\LdapGroupResolver;
use Throwable;

final class FakeLdapGroupResolver extends LdapGroupResolver
{
    /**
     * @var array<int, string>
     */
    public array $groups = [];

    public ?Throwable $failure = null;

    /**
     * @var array<int, string>
     */
    public array $resolvedEmails = [];

    /**
     * @return array<int, string>
     */
    public function resolveByEmail(string $email): array
    {
        $this->resolvedEmails[] = $email;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->groups;
    }
}
