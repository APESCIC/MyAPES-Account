# Access admin UX

**Date:** 2026-08-25  
**Issue:** [#97](https://github.com/APESCIC/MyAPES-Account/issues/97)  
**Parent:** [#91](https://github.com/APESCIC/MyAPES-Account/issues/91)  
**Branch:** `cursor/feature/97-access-admin-ux`

## Problem

Super Admin Groups, Roles, and Permissions are three separate surfaces with long permission checkbox lists. Editing a group mapping or a job-role permission set requires scanning 70+ raw keys. Protected roles remain visible in create/edit flows where they should stay code-owned and read-only.

## Decisions

| Decision | Choice |
| --- | --- |
| Workspace | Single `/admin/access` with tabs: Groups · Job roles · Permission catalogue |
| Old URLs | `/admin/groups`, `/admin/roles`, `/admin/permissions` redirect to the matching Access tab |
| Nav | One **Access** item replaces Groups / Roles / Permissions in Super Admin nav |
| Group Enable | Dropped — managed groups stay always enabled; row shows status only |
| Group Access tier | Read-only protected (immutable) mapping label |
| Group Job role | Mutable optional custom/job-role mapping (existing service) |
| Primary CTA | **Sync from Cloudron** (existing `RunDirectorySync` queue path) |
| Job role packs | Fixed reviewed packs (below); Advanced expands fine-grained non–Super-Admin catalogue |
| Protected roles | Hidden from Job roles create/edit/list of editable roles |
| Implementation | Blade `AdminAccessController` + reuse existing mutation services |
| Enable/disable API | Remains unavailable (no regression of #94/#96 always-enabled rule) |

## Capability packs

Packs are UI conveniences only. Saving still stores the resolved permission name list via `AuthorizationRoleManagementService`. Super Admin–only permissions never appear in packs or Advanced.

| Pack key | Title | Permissions |
| --- | --- | --- |
| `admin-overview` | Admin overview | `admin.analytics.view` |
| `view-accounts` | View accounts | `admin.users.view` |
| `manage-accounts` | Manage accounts | `admin.users.manage` |
| `staff-module-work` | Staff module work | All shipped module permissions whose ability is not `delete` and that require directory context (staff work abilities such as `*.view-all`, `*.update-all`, `*.assign`, `*.close`, …) |
| `module-delete` | Module delete | All shipped module permissions whose ability is `delete` |

Pack checked state:

- **On** when every permission in the pack is present on the role  
- **Off** when none are present  
- **Indeterminate** (Advanced-only hint) when a subset is present — toggling On adds missing keys; toggling Off removes the pack’s keys only

Advanced lists every assignable catalogue permission (existing `AdminRoleController` assignable set) grouped as today, collapsed by default.

## Information architecture

```
/admin/access?tab=groups|job-roles|permissions
  Groups tab     → catalogue + tier + optional job role + Sync CTA
  Job roles tab  → custom/default job roles list + pack editor
  Permissions    → read-only catalogue (current Admin Permissions content)

Redirects:
  /admin/groups*       → /admin/access?tab=groups
  /admin/roles*        → /admin/access?tab=job-roles (show → job role detail when id present)
  /admin/permissions*  → /admin/access?tab=permissions
```

Role detail editing lives under Access Job roles (`/admin/access/job-roles/{role}` or query equivalent). Create stays on the Job roles tab.

## Components

- **`AdminAccessController`** — tab index, job-role show/store/update/destroy, group mapping store/destroy, sync (delegate to existing services)
- **`JobRoleCapabilityPacks`** (support class) — pack definitions, expand/collapse to permission lists, checked-state helpers
- **Blade** — `resources/views/admin/access/*` (layout with tabs, groups, job-roles index/show, permissions)
- **Nav** — `superadmin/_navigation.blade.php` Access entry gated by any of groups/roles/permissions view abilities
- **Services unchanged in behaviour** — `DirectoryGroupMappingService`, `AuthorizationRoleManagementService`, `ManualDirectorySyncQueueResolver`, denial audit middleware

## Guarantees

- Immutable protected group→tier mappings cannot be changed or removed  
- Job-role mappings remain mutable and cannot replace missing directory eligibility  
- Provenance rules for local assignments unchanged  
- Denial auditing for Access mutations continues via existing admin denial middleware and privileged mutation audits  

## Out of scope

- Reintroducing group enable/disable  
- Editing protected role permissions  
- Changing Cloudron sync semantics beyond the existing Sync CTA  
- Access workspace for non–Super-Admin actors beyond current ability gates  
- Livewire / SPA rewrite  

## Acceptance mapping

| Acceptance | Design coverage |
| --- | --- |
| Change group mapping and job-role pack without scanning 70+ checkboxes by default | Groups optional job-role control; Job roles pack toggles + Advanced |
| Protected / immutable rules unchanged and tested | Read-only access tier; existing mapping service denials; regression tests |
| AdminAccess / directory management tests updated | New Access routes, redirects, nav, pack editor coverage |
