<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RoleMapper;
use Tests\TestCase;

class RoleMapperTest extends TestCase
{
    private RoleMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'myapes.roles.staff_groups' => [
                'position.staff',
                'position.students',
                'position.volunteers',
            ],
            'myapes.roles.admin_groups' => ['intranet.administrator'],
            'myapes.roles.superadmin_groups' => ['intranet.superadmin'],
        ]);

        $this->mapper = app(RoleMapper::class);
    }

    public function test_each_approved_position_group_maps_to_staff(): void
    {
        foreach (config('myapes.roles.staff_groups') as $group) {
            $this->assertSame(User::ROLE_STAFF, $this->mapper->map([$group]));
        }
    }

    public function test_administrator_and_superadmin_groups_map_to_their_roles(): void
    {
        $this->assertSame(
            User::ROLE_ADMIN,
            $this->mapper->map(['intranet.administrator'])
        );
        $this->assertSame(
            User::ROLE_SUPERADMIN,
            $this->mapper->map(['intranet.superadmin'])
        );
    }

    public function test_higher_privilege_groups_take_precedence(): void
    {
        $this->assertSame(
            User::ROLE_SUPERADMIN,
            $this->mapper->map([
                'position.staff',
                'intranet.administrator',
                'intranet.superadmin',
            ])
        );
        $this->assertSame(
            User::ROLE_ADMIN,
            $this->mapper->map([
                'position.volunteers',
                'intranet.administrator',
            ])
        );
    }

    public function test_group_matching_is_case_insensitive_and_trimmed(): void
    {
        $this->assertSame(
            User::ROLE_ADMIN,
            $this->mapper->map(['  INTRANET.ADMINISTRATOR  '])
        );
    }

    public function test_no_approved_group_returns_null_instead_of_a_public_role(): void
    {
        $this->assertNull($this->mapper->map([]));
        $this->assertNull($this->mapper->map(['unrelated.group']));
    }
}
