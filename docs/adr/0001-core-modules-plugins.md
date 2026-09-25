# ADR 0001: Core, Modules, and Plugins

- **Status:** Accepted
- **Date:** 2026-09-25
- **Issue:** [#279](https://github.com/APESCIC/MyAPES-Account/issues/279)
- **Epic:** [#278](https://github.com/APESCIC/MyAPES-Account/issues/278) (milestone v0.37.0 Beta)
- **Inventory:** [docs/architecture-inventory.md](../architecture-inventory.md) ([#280](https://github.com/APESCIC/MyAPES-Account/issues/280))

## Context

MyAPES Account is one Laravel application. Sign-in, accounts, access control, the Admin shell, and the organisation areas (APES CIC, APES Pet Care Clinic, APES Shelter and Rescue) all live in a flat `app/` tree, with feature code (tickets, cases, recruitment, consultations, pet profiles) mixed into the same models, controllers, policies, and `routes/web.php`.

The words in the code do not match the product:

| What people mean | Code today | Admin UI today |
| --- | --- | --- |
| Organisation area | **sub-core** (`SubCoreDefinition`, `sub_core_key`, middleware `service.selected:{subCore}`) | Sometimes "service" |
| Reusable feature | **module** / module type (`ModuleDefinition`, `module_key`, `module.available:{subCore},{module}`, tables `module_installations` / `module_settings`) | **Plugins** (`/admin/modules`) |
| Area × feature that is actually switched on | **module instance** (`apes-cic:recruitment`) | A row on the Plugins index |

`FirstPartyModuleRegistry` hard-codes the three areas, five feature types, the enablement matrix, navigation, dependencies, and permissions named `{subCore}.{module}.{ability}`. Adding an area or a feature means editing that Core class. Nothing stops Core from importing a feature class, or one feature from reaching into another.

Later work depends on one shared vocabulary: plugin manifests for language and keywords ([#264](https://github.com/APESCIC/MyAPES-Account/issues/264) / [#267](https://github.com/APESCIC/MyAPES-Account/issues/267)), and a Core auth boundary for account security. This record is that vocabulary. It does not move code.

## Decision

The application has three layers, in this order: **Core > Modules > Plugins**.

### Core

Platform that is always on. Sign-in and auth (local public accounts and Cloudron OIDC), accounts, profiles, access (directory groups, job roles, permissions), notifications, settings, the unified Admin shell, maintenance, audit, health, change log, and shared attachments.

Core never depends on a Module or a Plugin. It exposes contracts (registry, navigation, dashboard providers, settings, permissions, a `ModuleContext` for the current area) that the layers above implement.

### Modules

Organisation areas. Each has its own navigation, route prefix, hub, and staff. Each chooses which plugins it enables.

| Slug (canonical) | Name | Live route prefix (kept) |
| --- | --- | --- |
| `apes-cic` | APES CIC | `/apes-cic` |
| `pet-care-clinic` | APES Pet Care Clinic | `/petcare` |
| `shelter-rescue` | APES Shelter and Rescue | `/shelter` |

A module may depend on Core and on the plugins it enables, and only through those plugins' public contracts. Modules never import other modules.

### Plugins

Reusable feature packages with a manifest, enabled per module. Shipped today:

| Slug | Name | Enabled on (today) |
| --- | --- | --- |
| `tickets` | Tickets | all three modules |
| `cases` | Cases | `apes-cic`, `shelter-rescue` |
| `recruitment` | Recruitment | `apes-cic` only |
| `consultations` | Consultations | `pet-care-clinic` only |
| `pet-profiles` | Pet Profiles | `pet-care-clinic`, `shelter-rescue` (shared) |

A plugin depends on Core contracts, plus plugins it **explicitly** declares (today: `shelter-rescue` cases → pet profiles, and pet-care consultations → pet profiles). Plugins never reach into a module's internals. They receive the current module as context (slug, route prefix, settings) instead of hard-coding `apes-cic`.

Recruitment's vacancy model stays `RecruitmentRole`. That is not the Access job-role model and not Spatie's `Role`.

### Rename map

Use the new words in docs, issues, and new code. Do not rename PHP, database columns, or URLs in this decision.

| Old term | New term |
| --- | --- |
| sub-core | **Module** |
| service area, Service (when it means an organisation area) | **Module** |
| module, module type (when it means a feature) | **Plugin** |
| module instance (`{sub-core}:{module}`) | **Plugin enablement** (one module × one plugin) |
| `sub_core_key` | module slug |
| `module_key` | plugin slug |
| `module_installations` / `module_settings` | plugin enablement and plugin settings (table names stay until a later child renames them) |
| `{subCore}.{module}.{ability}` | `{module}.{plugin}.{ability}` (same strings; see below) |

Deprecated synonyms for the glossary ([#267](https://github.com/APESCIC/MyAPES-Account/issues/267)): sub-core, service area, and "module" used to mean a feature. UI wording stays in `docs/glossary.md` when that issue lands. This record is the source for the layer names; the glossary is the source for labels on screen.

### Dependency rules

These are the statements [#294](https://github.com/APESCIC/MyAPES-Account/issues/294) will enforce. They are not enforced yet.

1. Nothing under Core may import a Module or Plugin namespace.
2. A module may import Core and the plugins it enables, only through those plugins' public contracts and manifests.
3. A plugin may import Core contracts and plugins it declares as dependencies. It may not import a module, and it may not import an undeclared plugin.
4. A module may not import another module.
5. A plugin receives the current module as context. It does not hard-code a module slug to reach that module's internals.

### Where code will live

[#281](https://github.com/APESCIC/MyAPES-Account/issues/281) scaffolds this layout. This ADR approves the new base folders. They are not created in the documentation pull request.

```
app/Core/...                         App\Core\          (auth, accounts, profiles, access, notifications, settings, admin, maintenance, audit, attachments, extensions)
modules/<slug>/src/...               Modules\<Studly>\  modules/apes-cic → Modules\ApesCic
modules/<slug>/{routes,resources/views,lang,config,database/migrations,tests}
plugins/<slug>/src/...               Plugins\<Studly>\  plugins/pet-profiles → Plugins\PetProfiles
plugins/<slug>/{routes,resources/views,lang,config,database/migrations,database/factories,tests}
```

Each module and plugin has one service provider, discovered from the registries ([#283](https://github.com/APESCIC/MyAPES-Account/issues/283), [#287](https://github.com/APESCIC/MyAPES-Account/issues/287)), not hand-listed in `bootstrap/providers.php`. Core providers stay in `bootstrap/providers.php`. `App\` remains for the Laravel skeleton until moved code changes namespace.

A request flows: Core middleware (auth, account ready, maintenance) → module route group (prefix + module context) → plugin controller, with a `ModuleContext` (slug, route prefix, settings) rather than `defaults('subCoreKey')` copied onto every route.

### Permissions

Confirmed for [#292](https://github.com/APESCIC/MyAPES-Account/issues/292):

| Layer | Pattern | What we do with names that already exist |
| --- | --- | --- |
| Plugin in a module | `{module}.{plugin}.{ability}` | **Keep every current string.** `apes-cic.recruitment.review-applications` does not change. Grants stay attached with no data migration. |
| Core | `admin.*` and `superadmin.access` | **Keep.** Do not rename to `core.*` in this epic. A `core.*` alias would be a later, explicit migration. |
| Module-only | `{module}.{ability}` | No such permissions exist today. Add them only when a module hub needs its own ability. |
| Plugin global | `plugin.{plugin}.{ability}` | Not used today. Do not invent a second name for abilities that are already module-scoped. |

If a future child must change a stored name: rename the `permissions` row in place (same id) so `role_has_permissions`, `model_has_permissions`, and `permission_sources` stay attached; keep a read-time alias for one minor release; flush the permission cache; and report grant counts before and after. Disabled module × plugin pairs keep their stored permissions and hide them from editors ([#288](https://github.com/APESCIC/MyAPES-Account/issues/288)).

### URLs

Confirmed for [#293](https://github.com/APESCIC/MyAPES-Account/issues/293):

- Core stays unprefixed or under `/admin/*`. Legacy `/superadmin/*` redirects stay.
- Module prefixes stay `/apes-cic`, `/shelter`, and `/petcare`. Do not rename them to the slugs.
- Plugin URLs stay `/{module prefix}/{plugin path}` (`/tickets`, `/cases`, `/pets`, `/consultations`, `/apes-cic/recruitment`). `/pets` is the pet-profiles path; do not rename it to `/pet-profiles`.
- Module-independent public plugin URLs stay put. Recruitment's public board remains `/recruitment`.
- Admin plugin settings stay `/admin/modules/{subCoreKey}/{moduleKey}/settings`.
- Route names stay (`apes-cic.*`, `shelter.*`, `petcare.*`, `recruitment.*`, `admin.*`).
- Anything that does move later gets a **301** from the old URL. Stored notification links use absolute URLs, so the old URL must keep resolving. Existing 302 closure redirects become permanent redirects only when the target is the stable canonical URL.

### Migration strategy (not executed here)

| When | What changes | What stays |
| --- | --- | --- |
| This PR ([#279](https://github.com/APESCIC/MyAPES-Account/issues/279), [#280](https://github.com/APESCIC/MyAPES-Account/issues/280)) | Docs only: this ADR, `docs/architecture.md`, the inventory, `AGENTS.md`, `PRODUCT.md` vocabulary | All PHP, routes, migrations, permissions, URLs |
| [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) | Empty `modules/` and `plugins/` skeletons, PSR-4, `Relation::enforceMorphMap()` **before** any model moves, plus a data migration from stored FQCNs to stable aliases | Route list unchanged |
| [#282](https://github.com/APESCIC/MyAPES-Account/issues/282)–[#291](https://github.com/APESCIC/MyAPES-Account/issues/291) | Move classes into Core, module, and plugin namespaces. `git mv` plus namespace edits. One plugin at a time, Pet Profiles before Cases and Consultations | Permission strings, live URLs, table names such as `shelter_cases` |
| [#292](https://github.com/APESCIC/MyAPES-Account/issues/292) | Only if a name must change: in-place permission rename that preserves ids | Every existing grant's effective abilities |
| [#293](https://github.com/APESCIC/MyAPES-Account/issues/293) | 301s only for URLs that actually move | Every GET URL that works on v0.36.0 |

`audit_logs.auditable_type`, `support_attachments.attachable_type`, `notifications.notifiable_type`, and Spatie `model_type` store class names today. [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) maps those to stable aliases before any model changes namespace. The inventory lists the couplings those moves have to break.

## Alternatives considered

### Flat `app/` with namespaces only

Keep one tree and rename namespaces (`App\Plugins\Tickets\...`) without new base folders. Rejected: views, migrations, routes, and language files would still sit in shared folders, and a plugin could not be added without editing Core routes and providers. The dependency rules would be harder to see in review.

### `nwidart/laravel-modules`

Use a third-party package that owns module discovery, migrations, and views. Rejected: the app needs two layers above Core (organisation areas and reusable features), not one module concept. A package would also become a dependency Core has to learn, which this epic is trying to avoid. The in-repo layout covers discovery with one service provider per package.

### In-repo `modules/` and `plugins/` folders

Chosen. Matches the three layers, keeps Laravel's `app/` skeleton, and lets [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) add PSR-4 plus a provider per package without a new Composer dependency. Deploy packaging must include the new folders when they exist (`scripts/deploy/package-release.sh` in [#281](https://github.com/APESCIC/MyAPES-Account/issues/281)).

## Consequences

- New base folders `app/Core`, `modules/<slug>`, and `plugins/<slug>` are approved for [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) onward. `AGENTS.md` records that approval. This pull request does not create them.
- Docs and new issues use Core, Module, Plugin, plugin enablement, and plugin settings. Old names stay in the code until the child that moves that code.
- User-facing labels still follow the glossary ([#267](https://github.com/APESCIC/MyAPES-Account/issues/267)). Until `docs/glossary.md` exists, do not rename buttons or nav in the name of this ADR.
- Permission grants and live URLs are preserved by not renaming them. The structure epic is a move, not a new product.
- Architecture tests do not fail CI until [#294](https://github.com/APESCIC/MyAPES-Account/issues/294) turns them on. Until then, review uses the inventory.
