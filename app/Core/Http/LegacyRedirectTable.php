<?php

namespace App\Core\Http;

/**
 * Legacy URL redirect table for Structure (#293).
 *
 * Live prefixes stay `/apes-cic`, `/shelter`, `/petcare`; pets stay `/pets`;
 * public Recruitment stays `/recruitment*`. Entries with status 301 are active
 * permanent redirects. Placeholder rows (status null) document future moves
 * when Waves 5–7 relocate plugin routes — no redirect is registered until then.
 *
 * @phpstan-type RedirectRow array{
 *     from: string,
 *     to_route: string|null,
 *     to_path: string|null,
 *     status: int|null,
 *     name: string|null,
 *     note: string
 * }
 */
final class LegacyRedirectTable
{
    /**
     * @return list<RedirectRow>
     */
    public static function entries(): array
    {
        return [
            [
                'from' => '/superadmin',
                'to_route' => 'admin.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'superadmin.index',
                'note' => 'Unified Admin shell (#252). Permanent as of #293.',
            ],
            [
                'from' => '/superadmin/groups',
                'to_route' => 'admin.groups.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'superadmin.groups',
                'note' => 'Legacy Super Admin groups → Admin groups.',
            ],
            [
                'from' => '/superadmin/plugins',
                'to_route' => 'admin.modules.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'superadmin.plugins',
                'note' => 'Legacy Super Admin plugins → Admin Plugins index.',
            ],
            [
                'from' => '/superadmin/modules',
                'to_route' => 'admin.modules.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'superadmin.modules',
                'note' => 'Legacy Super Admin modules → Admin Plugins index.',
            ],
            [
                'from' => '/admin/groups',
                'to_route' => 'admin.access.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'admin.groups.index',
                'note' => 'Access tab deep-link (groups).',
            ],
            [
                'from' => '/admin/roles',
                'to_route' => 'admin.access.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'admin.roles.index',
                'note' => 'Access tab deep-link (job-roles).',
            ],
            [
                'from' => '/admin/permissions',
                'to_route' => 'admin.access.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'admin.permissions.index',
                'note' => 'Access tab deep-link (permissions).',
            ],
            [
                'from' => '/shelter/pet-profiles',
                'to_route' => 'shelter.pets.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'shelter.pet-profiles',
                'note' => 'Pet profiles path is /shelter/pets; old segment redirects.',
            ],
            [
                'from' => '/petcare/pet-profiles',
                'to_route' => 'petcare.pets.index',
                'to_path' => null,
                'status' => 301,
                'name' => 'petcare.pet-profiles',
                'note' => 'Pet profiles path is /petcare/pets; old segment redirects.',
            ],
            // Placeholders until Waves 5–7 move plugin controllers into packages.
            [
                'from' => '/apes-cic/tickets',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: URI stays; route file moves to plugins/tickets in #289.',
            ],
            [
                'from' => '/shelter/cases',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: URI stays; route file moves to plugins/cases in #289.',
            ],
            [
                'from' => '/petcare/pets',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: URI stays; route file moves to plugins/pet-profiles in #291.',
            ],
            [
                'from' => '/recruitment',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: public Recruitment URI stays; package routes in #290.',
            ],
            [
                'from' => '/apes-cic/recruitment',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: staff Recruitment URI stays; package routes in #290.',
            ],
            [
                'from' => '/petcare/consultations',
                'to_route' => null,
                'to_path' => null,
                'status' => null,
                'name' => null,
                'note' => 'Placeholder: URI stays; route file moves to plugins/consultations in #290.',
            ],
        ];
    }

    /**
     * Active permanent redirects only (status === 301).
     *
     * @return list<RedirectRow>
     */
    public static function activePermanent(): array
    {
        return array_values(array_filter(
            self::entries(),
            static fn (array $row): bool => $row['status'] === 301,
        ));
    }

    /**
     * Stable live prefixes that must never be renamed for slug tidiness.
     *
     * @return array<string, string> module slug → live URI prefix
     */
    public static function modulePrefixes(): array
    {
        return [
            'apes-cic' => '/apes-cic',
            'shelter-rescue' => '/shelter',
            'pet-care-clinic' => '/petcare',
        ];
    }
}
