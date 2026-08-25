# Access Admin UX Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace Super Admin Groups / Roles / Permissions with a single `/admin/access` workspace (tabs + capability packs) while preserving immutable mappings, provenance, and denial audits.

**Architecture:** Blade-first `AdminAccessController` owns the Access UI. Mutation behaviour stays in `DirectoryGroupMappingService` and `AuthorizationRoleManagementService`. `JobRoleCapabilityPacks` expands fixed pack keys into permission name lists for forms. Old `/admin/groups|roles|permissions` routes become redirects into Access.

**Tech Stack:** Laravel Blade, PHPUnit feature tests, existing Spatie-backed roles/permissions, existing admin denial middleware.

## Global Constraints

- Branch: `cursor/feature/97-access-admin-ux` (already created; design commit present)
- Issue: `#97` — PR body must include `Fixes #97`
- No group enable/disable UI or API restoration
- Super Admin–only permissions never in packs or Advanced
- Protected roles never creatable/editable from Job roles tab
- Release metadata in the same PR (`minor` capability bump from current `VERSION`)
- No Aikido
- Tests use PHPUnit (`php artisan test --filter=...`), not Pest
- Preserve existing design system (panels, tables, forms) — no purple/glow redesign

## File structure

| Path | Responsibility |
| --- | --- |
| `app/Support/JobRoleCapabilityPacks.php` | Pack definitions, expand packs → permissions, checked-state helpers |
| `app/Http/Controllers/Admin/AdminAccessController.php` | Access tabs + job-role CRUD + group mapping + sync |
| `resources/views/admin/access/layout.blade.php` | Shared Access chrome + tabs |
| `resources/views/admin/access/groups.blade.php` | Groups tab |
| `resources/views/admin/access/job-roles/index.blade.php` | Job roles list + create |
| `resources/views/admin/access/job-roles/show.blade.php` | Job role editor (packs + Advanced) |
| `resources/views/admin/access/permissions.blade.php` | Read-only catalogue |
| `resources/views/superadmin/_navigation.blade.php` | Single Access nav item |
| `routes/web.php` | Access routes + redirects from old admin URLs |
| `tests/Unit/JobRoleCapabilityPacksTest.php` | Pack expand / state unit tests |
| `tests/Feature/AdminAccessWorkspaceTest.php` | New Access UX + redirects |
| Update existing Admin* feature tests | Route names, copy, denial audits |

Old `AdminGroupController` / `AdminRoleController` / `AdminPermissionController` remain as thin redirect endpoints OR are replaced by named redirect routes — prefer **route-level redirects** so denial middleware still has named routes for sync/mappings/mutations on Access.

---

### Task 1: JobRoleCapabilityPacks

**Files:**
- Create: `app/Support/JobRoleCapabilityPacks.php`
- Create: `tests/Unit/JobRoleCapabilityPacksTest.php`
- Modify: none

**Interfaces:**
- Consumes: `App\Contracts\ModuleRegistry` (module permission descriptors)
- Produces:
  - `JobRoleCapabilityPacks::definitions(ModuleRegistry $modules): array` keyed by pack key → `{title: string, permissions: list<string>}`
  - `JobRoleCapabilityPacks::expand(array $packKeys, ModuleRegistry $modules): list<string>`
  - `JobRoleCapabilityPacks::state(string $packKey, array $selectedPermissions, ModuleRegistry $modules): 'on'|'off'|'indeterminate'`
  - `JobRoleCapabilityPacks::merge(array $selectedPermissions, array $packKeysOn, array $packKeysOff, ModuleRegistry $modules): list<string>`

- [ ] **Step 1: Write the failing unit test**

```php
<?php

namespace Tests\Unit;

use App\Contracts\ModuleRegistry;
use App\Support\JobRoleCapabilityPacks;
use Tests\TestCase;

class JobRoleCapabilityPacksTest extends TestCase
{
    public function test_fixed_packs_expand_to_reviewed_permission_sets(): void
    {
        $modules = app(ModuleRegistry::class);
        $definitions = JobRoleCapabilityPacks::definitions($modules);

        $this->assertSame(
            ['admin.analytics.view'],
            $definitions['admin-overview']['permissions'],
        );
        $this->assertSame(
            ['admin.users.view'],
            $definitions['view-accounts']['permissions'],
        );
        $this->assertSame(
            ['admin.users.manage'],
            $definitions['manage-accounts']['permissions'],
        );

        foreach ($definitions['staff-module-work']['permissions'] as $permission) {
            $descriptor = $modules->permission($permission);
            $this->assertNotNull($descriptor);
            $this->assertTrue($descriptor->requiresDirectoryContext);
            $this->assertNotSame('delete', $descriptor->ability);
        }

        foreach ($definitions['module-delete']['permissions'] as $permission) {
            $descriptor = $modules->permission($permission);
            $this->assertNotNull($descriptor);
            $this->assertSame('delete', $descriptor->ability);
        }

        $this->assertSame(
            'on',
            JobRoleCapabilityPacks::state(
                'view-accounts',
                ['admin.users.view', 'admin.analytics.view'],
                $modules,
            ),
        );
        $this->assertSame(
            'off',
            JobRoleCapabilityPacks::state('view-accounts', [], $modules),
        );
        $this->assertSame(
            'indeterminate',
            JobRoleCapabilityPacks::state(
                'management-like',
                ['admin.analytics.view'],
                $modules,
            ),
        );
    }
}
```

Fix the indeterminate case to use a real multi-permission pack:

```php
$this->assertSame(
    'indeterminate',
    JobRoleCapabilityPacks::state(
        'staff-module-work',
        [JobRoleCapabilityPacks::definitions($modules)['staff-module-work']['permissions'][0]],
        $modules,
    ),
);
```

Also assert merge on/off behaviour and that expand never returns Super Admin–only permissions (`admin.roles.manage`, etc.).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=JobRoleCapabilityPacksTest`

Expected: FAIL — class not found

- [ ] **Step 3: Implement `JobRoleCapabilityPacks`**

```php
<?php

namespace App\Support;

use App\Contracts\ModuleRegistry;
use App\Services\AuthorizationProfile;

final class JobRoleCapabilityPacks
{
    /**
     * @return array<string, array{title: string, permissions: array<int, string>}>
     */
    public static function definitions(ModuleRegistry $modules): array
    {
        $staffWork = [];
        $moduleDelete = [];

        foreach ($modules->permissions() as $permission) {
            if (! $permission->requiresDirectoryContext) {
                continue;
            }

            if ($permission->ability === 'delete') {
                $moduleDelete[] = $permission->name;
            } else {
                $staffWork[] = $permission->name;
            }
        }

        sort($staffWork);
        sort($moduleDelete);

        return [
            'admin-overview' => [
                'title' => 'Admin overview',
                'permissions' => ['admin.analytics.view'],
            ],
            'view-accounts' => [
                'title' => 'View accounts',
                'permissions' => ['admin.users.view'],
            ],
            'manage-accounts' => [
                'title' => 'Manage accounts',
                'permissions' => ['admin.users.manage'],
            ],
            'staff-module-work' => [
                'title' => 'Staff module work',
                'permissions' => $staffWork,
            ],
            'module-delete' => [
                'title' => 'Module delete',
                'permissions' => $moduleDelete,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $packKeys
     * @return array<int, string>
     */
    public static function expand(array $packKeys, ModuleRegistry $modules): array
    {
        $definitions = self::definitions($modules);
        $permissions = [];

        foreach ($packKeys as $key) {
            foreach ($definitions[$key]['permissions'] ?? [] as $permission) {
                $permissions[] = $permission;
            }
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return $permissions;
    }

    /**
     * @param  array<int, string>  $selectedPermissions
     * @return 'on'|'off'|'indeterminate'
     */
    public static function state(
        string $packKey,
        array $selectedPermissions,
        ModuleRegistry $modules,
    ): string {
        $pack = self::definitions($modules)[$packKey]['permissions'] ?? [];

        if ($pack === []) {
            return 'off';
        }

        $selected = array_fill_keys($selectedPermissions, true);
        $hits = 0;

        foreach ($pack as $permission) {
            if (isset($selected[$permission])) {
                $hits++;
            }
        }

        if ($hits === 0) {
            return 'off';
        }

        if ($hits === count($pack)) {
            return 'on';
        }

        return 'indeterminate';
    }

    /**
     * @param  array<int, string>  $selectedPermissions
     * @param  array<int, string>  $packKeysOn
     * @param  array<int, string>  $packKeysOff
     * @return array<int, string>
     */
    public static function merge(
        array $selectedPermissions,
        array $packKeysOn,
        array $packKeysOff,
        ModuleRegistry $modules,
    ): array {
        $set = array_fill_keys($selectedPermissions, true);

        foreach (self::expand($packKeysOff, $modules) as $permission) {
            unset($set[$permission]);
        }

        foreach (self::expand($packKeysOn, $modules) as $permission) {
            $set[$permission] = true;
        }

        $permissions = array_keys($set);
        sort($permissions);

        return $permissions;
    }
}
```

Do not inject `AuthorizationProfile` into pack definitions for filtering — Advanced already excludes Super Admin–only keys in the controller. Pack fixed keys are not Super Admin–only.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=JobRoleCapabilityPacksTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Support/JobRoleCapabilityPacks.php tests/Unit/JobRoleCapabilityPacksTest.php
git commit -m "Add job-role capability pack helpers for Access admin UX."
```

---

### Task 2: Access routes, controller shell, nav, redirects

**Files:**
- Create: `app/Http/Controllers/Admin/AdminAccessController.php`
- Create: `resources/views/admin/access/layout.blade.php`
- Create: `resources/views/admin/access/groups.blade.php` (minimal stub OK)
- Create: `resources/views/admin/access/job-roles/index.blade.php` (stub)
- Create: `resources/views/admin/access/permissions.blade.php` (stub)
- Modify: `routes/web.php` (admin group)
- Modify: `resources/views/superadmin/_navigation.blade.php`
- Create: `tests/Feature/AdminAccessWorkspaceTest.php` (redirect + nav tests first)

**Interfaces:**
- Consumes: existing Gate abilities `admin.groups.view`, `admin.roles.view`, `admin.permissions.view`
- Produces routes:
  - `GET admin/access` → `admin.access.index` (`?tab=groups|job-roles|permissions`)
  - `POST admin/access/sync` → `admin.access.sync`
  - `POST admin/access/groups/{directoryGroup}/mappings` → `admin.access.mappings.store`
  - `DELETE admin/access/mappings/{mapping}` → `admin.access.mappings.destroy`
  - `POST admin/access/job-roles` → `admin.access.job-roles.store`
  - `GET admin/access/job-roles/{role}` → `admin.access.job-roles.show`
  - `PUT admin/access/job-roles/{role}` → `admin.access.job-roles.update`
  - `DELETE admin/access/job-roles/{role}` → `admin.access.job-roles.destroy`
- Redirects (302):
  - `admin/groups` → `admin/access?tab=groups`
  - `admin/groups/sync` POST → keep working via new `admin.access.sync` OR redirect after POST from old name — **replace** old sync route with Access sync; old path redirect GET only; for POST `/admin/groups/sync` map to Access controller sync method under both names during transition, then update tests to `admin.access.sync`
  - `admin/roles` → `admin/access?tab=job-roles`
  - `admin/roles/{role}` → `admin/access/job-roles/{role}`
  - `admin/permissions` → `admin/access?tab=permissions`

Practical redirect strategy that keeps tests maintainable:

1. Register Access routes as the real handlers.
2. Change old route definitions to `Redirect::to(...)` closures for GET.
3. Point old mutation route names used by tests at Access controller methods **or** update all tests in Task 7 to new names — prefer **updating tests to new names** and leaving GET redirects only.

- [ ] **Step 1: Write failing workspace tests**

```php
public function test_access_workspace_tabs_and_legacy_redirects(): void
{
    $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

    $this->actingAs($superAdmin)
        ->get('/admin/access?tab=groups')
        ->assertOk()
        ->assertSee('Access')
        ->assertSee('Groups')
        ->assertSee('Job roles')
        ->assertSee('Permission catalogue');

    $this->actingAs($superAdmin)
        ->get('/admin/groups')
        ->assertRedirect('/admin/access?tab=groups');

    $this->actingAs($superAdmin)
        ->get('/admin/roles')
        ->assertRedirect('/admin/access?tab=job-roles');

    $this->actingAs($superAdmin)
        ->get('/admin/permissions')
        ->assertRedirect('/admin/access?tab=permissions');

    $this->actingAs($superAdmin)
        ->get('/superadmin')
        ->assertOk()
        ->assertSee('Access')
        ->assertDontSee('>Groups</a>', false)
        ->assertDontSee('>Roles</a>', false)
        ->assertDontSee('>Permissions</a>', false);
}
```

Adjust nav assertions to match actual markup (prefer `assertSeeText('Access')` and absence of separate nav labels via snapshot of `_navigation`).

- [ ] **Step 2: Run test — expect FAIL**

Run: `php artisan test --filter=AdminAccessWorkspaceTest::test_access_workspace_tabs_and_legacy_redirects`

- [ ] **Step 3: Implement routes + controller index + layout + nav**

`AdminAccessController::index`:

```php
public function index(Request $request): View|RedirectResponse
{
    $tab = $request->validate([
        'tab' => ['nullable', Rule::in(['groups', 'job-roles', 'permissions'])],
    ])['tab'] ?? 'groups';

    return match ($tab) {
        'groups' => $this->groupsTab($request),
        'job-roles' => $this->jobRolesTab($request),
        'permissions' => $this->permissionsTab($request),
    };
}
```

Gate each tab with the existing ability (`admin.groups.view`, `admin.roles.view`, `admin.permissions.view`). If the user lacks the tab’s ability, `abort(403)`.

Nav:

```blade
@canany(['admin.groups.view', 'admin.roles.view', 'admin.permissions.view'])
    <a href="{{ route('admin.access.index') }}" @if(request()->routeIs('admin.access.*')) aria-current="page" @endif>Access</a>
@endcanany
```

Remove the three separate Groups/Roles/Permissions links.

- [ ] **Step 4: Run test — expect PASS**

- [ ] **Step 5: Commit**

```bash
git commit -m "Add Access admin workspace shell with legacy redirects."
```

---

### Task 3: Groups tab (status, tier, optional job role, Sync)

**Files:**
- Modify: `AdminAccessController` groups methods
- Modify: `resources/views/admin/access/groups.blade.php`
- Modify: `tests/Feature/AdminAccessWorkspaceTest.php`
- Modify: `tests/Feature/AdminDirectoryManagementTest.php` (route names)
- Modify: `tests/Feature/AdminAccessAndViewsTest.php` (groups UI assertions)

**Interfaces:**
- Reuse `DirectoryGroupMappingService::map/remove`
- Reuse sync logic from current `AdminGroupController::sync`

- [ ] **Step 1: Write failing tests**

Assert Groups tab:

- Sees `Sync from Cloudron` (or keep existing button label **Queue manual directory synchronization** — prefer new copy **Sync from Cloudron** per spec; update tests accordingly)
- Sees access tier for `myapesaccount.staff` → `staff` with Preset Cloudron mapping
- Sees `Add job role mapping` / job role select
- Does **not** see Enable/Disable for this app
- Mapping POST `/admin/access/groups/{id}/mappings` adds mutable job role and keeps immutable staff mapping
- DELETE removes mutable mapping only

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Port groups UI into Access**

Row columns: Group · Status · Access tier · Optional job role · (mapping controls)

Access tier = first immutable protected role mapping on the group (or “None”).  
Optional job role = mutable custom role mappings with remove; add form for non-protected roles.

Move sync + mapping actions from `AdminGroupController` into `AdminAccessController`. Leave old GET `/admin/groups` as redirect only; remove or redirect POST `/admin/groups/sync` to Access sync (update all callers).

- [ ] **Step 4: Run targeted tests — PASS**

Run: `php artisan test --filter="AdminAccessWorkspaceTest|AdminDirectoryManagementTest|AdminAccessAndViewsTest::test_groups_ui"`

- [ ] **Step 5: Commit**

```bash
git commit -m "Move Groups management into the Access workspace."
```

---

### Task 4: Job roles tab — list, create, pack editor

**Files:**
- Modify: `AdminAccessController` job-role methods
- Create/modify: `resources/views/admin/access/job-roles/index.blade.php`
- Create/modify: `resources/views/admin/access/job-roles/show.blade.php`
- Modify: `tests/Feature/AdminAccessWorkspaceTest.php`
- Modify: `tests/Feature/AdminRoleAndUserManagementTest.php` (paths + pack UI)

**Interfaces:**
- Form posts:
  - `name` (kebab)
  - `packs[]` checked pack keys (on)
  - `permissions[]` Advanced fine-grained (authoritative union after merge)
- Server resolves final permission list:

```php
$packKeys = $validated['packs'] ?? [];
$advanced = $validated['permissions'] ?? [];
// Treat submitted packs as ON; compute permissions as unique sort of
// JobRoleCapabilityPacks::expand($packKeys) ∪ $advanced
// Prefer: Advanced is source of truth when expanded; packs only seed Advanced via JS optional.
// Spec: packs are UI conveniences. Simplest reliable server model:
$permissions = array_values(array_unique(array_merge(
    JobRoleCapabilityPacks::expand($packKeys, $modules),
    $advanced,
)));
sort($permissions);
```

Without JS: checking a pack checkbox submits pack key; Advanced checkboxes submit individual permissions. Server merges expand(packs) ∪ permissions. Unchecking a pack requires not sending that pack key; leftover Advanced boxes may keep permissions — document that Advanced is authoritative for partial packs. For create/update without JS:

**Server algorithm (no JS required):**

1. Start from submitted `permissions[]` (Advanced).
2. For each pack key in `packs[]`, add all pack permissions (ON).
3. Packs not submitted are not removed unless we also accept `packs_off` — better approach:

**Recommended form model:**

- Always render Advanced with current permissions (collapsed).
- Pack toggles are checkboxes named `packs[]`.
- On submit, controller:
  1. Loads current role permissions (update) or `[]` (create).
  2. Determines which packs were previously on via `state(...) === 'on'`.
  3. For packs newly off: remove their permissions.
  4. For packs newly on: add their permissions.
  5. Then apply Advanced `permissions[]` as the final set **if Advanced fieldset is present** (always present).

Simplest UX that matches acceptance: **Advanced is always submitted and is the final permission list.** Pack checkboxes are progressive enhancement that uses small inline JS to check/uncheck Advanced boxes. If JS disabled, user still uses Advanced.

Add minimal progressive enhancement in the Blade file:

```html
<script>
document.querySelectorAll('[data-pack]').forEach((pack) => {
  pack.addEventListener('change', () => {
    const perms = JSON.parse(pack.dataset.packPermissions);
    perms.forEach((name) => {
      const input = document.querySelector(`input[name="permissions[]"][value="${name}"]`);
      if (input) input.checked = pack.checked;
    });
  });
});
</script>
```

Server trusts `permissions[]` only (same as today’s role forms). Packs are UI helpers.

- [ ] **Step 1: Failing tests**

- Job roles tab lists default job roles, not `staff`/`super-admin`
- Create role with only Advanced `admin.users.view` works
- Update receptionist via Access show URL with packs UI visible (`Admin overview`, `View accounts`, …) and `<details>` Advanced
- Protected role show redirects or 404 from job-roles show
- Super Admin–only permission rejected

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Implement list/create/show/update/destroy**

Hide protected roles:

```php
Role::query()->where('guard_name', 'web')->where('is_protected', false)
```

Show/update/destroy: `findOrFail` then abort 404 if `is_protected`.

Reuse validation from `AdminRoleController` (extract private helpers into a shared trait `ValidatesCustomRoles` **or** duplicate the small validate method into Access controller — prefer small duplication over large refactor unless trait is trivial).

- [ ] **Step 4: Run — PASS**

Run: `php artisan test --filter="AdminAccessWorkspaceTest|AdminRoleAndUserManagementTest"`

- [ ] **Step 5: Commit**

```bash
git commit -m "Add Access Job roles tab with capability packs."
```

---

### Task 5: Permission catalogue tab

**Files:**
- Modify: `AdminAccessController::permissionsTab`
- Modify: `resources/views/admin/access/permissions.blade.php`
- Port filters/markup from `resources/views/admin/permissions/index.blade.php`

- [ ] **Step 1: Test**

```php
$this->actingAs($superAdmin)
    ->get('/admin/access?tab=permissions&q=users.view')
    ->assertOk()
    ->assertSee('admin.users.view')
    ->assertSee('View accounts')
    ->assertDontSee('admin.roles.manage'); // if filtered search excludes it; otherwise assert catalogue is read-only (no Update buttons)
```

- [ ] **Step 2–4: Implement by porting current permissions index into Access tab**

- [ ] **Step 5: Commit**

```bash
git commit -m "Add Access Permission catalogue tab."
```

---

### Task 6: Retire old admin surfaces from tests and thin controllers

**Files:**
- Modify: `AdminGroupController`, `AdminRoleController`, `AdminPermissionController` → redirect-only GET methods **or** delete unused actions and keep redirects in `routes/web.php`
- Modify: all tests referencing old route names/paths
- Grep: `admin.groups.`, `admin.roles.`, `admin.permissions.`, `/admin/groups`, `/admin/roles`, `/admin/permissions`

- [ ] **Step 1: Grep and list remaining references**

Run: `rg "admin\\.(groups|roles|permissions)\\.|/admin/(groups|roles|permissions)" tests routes resources`

- [ ] **Step 2: Update each test to Access routes; keep redirect coverage in AdminAccessWorkspaceTest**

Denial-audit tests must POST to `admin.access.sync`, `admin.access.job-roles.update`, etc., and expect those route names in audit context.

- [ ] **Step 3: Run broad suite**

Run: `php artisan test --filter="AdminAccess|AdminDirectory|AdminRole|DirectoryGroupMapping|AuthorizationGate|AuthorizationSchema"`

Expected: PASS

- [ ] **Step 4: Commit**

```bash
git commit -m "Point authorization admin tests at the Access workspace."
```

---

### Task 7: README + release metadata + PR

**Files:**
- Modify: `README.md` Administration bullets for Access workspace
- Run: `php artisan myapes:changelog-prepare --type=minor --title="Simplify Access admin for groups and job roles" --issue=97`
- Fill `TODO:` fields in `resources/data/releases.json`
- Fix version-pinned test leftovers (anchor, record count, date slices, desert/apes/phaseB indices, current refs) as in prior releases
- Validate: `git fetch origin && php artisan myapes:changelog-validate --base-ref=origin/main`
- Push branch; `gh pr create` with `Fixes #97`
- Point provenance at PR number; push
- Comment on issue #97 with PR URL

- [ ] **Step 1: README**

Replace Groups/Roles/Permissions bullets with Access workspace description matching the spec.

- [ ] **Step 2: Changelog prepare + fill + validate**

- [ ] **Step 3: Pre-merge smoke**

Run: `php artisan test --filter="AdminAccessWorkspaceTest|JobRoleCapabilityPacksTest|ChangeLogPageTest::test_guest|ReleaseHistoryCommandTest::test_source"`

- [ ] **Step 4: Commit release metadata + README**

```bash
git commit -m "Release vX.Y.Z Access admin workspace (issue #97)."
```

- [ ] **Step 5: Open PR, wait CI, merge when green, confirm Cloudron deploy, close #97**

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| `/admin/access` tabs | Task 2 |
| Legacy redirects | Task 2 |
| Nav Access item | Task 2 |
| Groups status / tier / job role / no Enable | Task 3 |
| Sync from Cloudron CTA | Task 3 |
| Job role packs + Advanced | Task 1 + 4 |
| Hide protected roles | Task 4 |
| Permission catalogue read-only | Task 5 |
| Immutable/protected rules preserved | Task 3 (reuse services) + existing mapping tests |
| AdminAccess / directory tests updated | Task 3 + 6 |
| Release in same PR | Task 7 |

## Placeholder / consistency review

- Pack keys: `admin-overview`, `view-accounts`, `manage-accounts`, `staff-module-work`, `module-delete` — used consistently
- Route names: `admin.access.*` — used consistently
- Server trusts `permissions[]`; packs are progressive enhancement — documented in Task 4
