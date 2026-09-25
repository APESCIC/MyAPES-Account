# Architecture

MyAPES Account is three layers: **Core > Modules > Plugins**.

The decision, the alternatives, and the migration strategy are in [ADR 0001](adr/0001-core-modules-plugins.md). This page is the map later work builds against. The file-by-file inventory is [architecture-inventory.md](architecture-inventory.md) ([#280](https://github.com/APESCIC/MyAPES-Account/issues/280)).

Nothing in those docs moves code. Routes, permissions, migrations, and URLs stay as they are until a later child of [#278](https://github.com/APESCIC/MyAPES-Account/issues/278) says otherwise.

## Layers

### Core

Always on. Core has no knowledge of a specific organisation area or feature.

- Sign-in and auth: local public accounts, and staff sign-in through Cloudron OIDC
- Accounts and profiles
- Access: directory groups, job roles, and permissions
- Notifications (the delivery mechanism, not a feature's message copy)
- Settings
- Unified Admin shell, including maintenance
- Audit, health, and the change log
- Shared attachments

Core depends only on the framework and vendor packages. It exposes contracts that modules and plugins implement: registry, navigation, dashboard providers, settings, permissions, and the current module context.

### Modules

Organisation areas. Each has its own navigation, route prefix, hub, and staff, and it chooses which plugins it enables.

| Module | Slug | Live prefix |
| --- | --- | --- |
| APES CIC | `apes-cic` | `/apes-cic` |
| APES Pet Care Clinic | `pet-care-clinic` | `/petcare` |
| APES Shelter and Rescue | `shelter-rescue` | `/shelter` |

The live prefix is not the slug. Bookmarks keep the prefix. See [URLs](#urls).

### Plugins

Reusable features. A module enables a plugin; the plugin does not belong to one module.

| Plugin | Slug | Shared? |
| --- | --- | --- |
| Tickets | `tickets` | All three modules. One controller and one view set today. |
| Cases | `cases` | APES CIC and Shelter and Rescue. One `ShelterCase` model, distinguished by the area slug. |
| Recruitment | `recruitment` | APES CIC only. Public board at `/recruitment`. Vacancy model is `RecruitmentRole` (never Spatie `Role`, never an Access job role). |
| Consultations | `consultations` | Pet Care Clinic only. Depends on Pet Profiles. |
| Pet Profiles | `pet-profiles` | Pet Care Clinic and Shelter and Rescue. URL segment is `/pets`. |

## Dependency rules

[#294](https://github.com/APESCIC/MyAPES-Account/issues/294) will turn these into CI checks. They are the rules to build against now.

1. Core does not import a Module or Plugin namespace.
2. A module depends on Core and on the plugins it enables, only through those plugins' public contracts and manifests.
3. A plugin depends on Core contracts, plus plugins named in its manifest. It does not import a module. It does not import a plugin it has not declared.
4. A module does not import another module.
5. A plugin receives the current module as context (slug, route prefix, settings). It does not hard-code a module slug to reach that module's internals.

## How a request flows

Today, `routes/web.php` copies `subCoreKey` and `moduleKey` onto each route, and middleware `service.selected:{subCore}` / `module.available:{subCore},{module}` gates them.

Target flow, once [#283](https://github.com/APESCIC/MyAPES-Account/issues/283) and [#287](https://github.com/APESCIC/MyAPES-Account/issues/287) land:

1. Core middleware: auth, account ready, maintenance, directory revalidation.
2. Module route group: the live prefix, and a `ModuleContext` for that module.
3. Plugin controller: the plugin's action, reading `ModuleContext` instead of a hard-coded area.

Public Recruitment is the exception that has no module prefix. It is still the Recruitment plugin, with APES CIC as the enabling module, gated by `module.available:apes-cic,recruitment`.

## Vocabulary

[#267](https://github.com/APESCIC/MyAPES-Account/issues/267) will add `docs/glossary.md` and `lang/en_GB/terms.php`. That glossary is the single source for **UI wording**. This page is the source for **layer names**. Use the same words in both.

| Canonical term | Meaning | Deprecated synonyms |
| --- | --- | --- |
| Core | The platform layer above | — |
| Module | An organisation area | sub-core, service area, Service (when it means the area) |
| Plugin | A reusable feature | module, module type (when it means the feature) |
| Plugin enablement | One module with one plugin switched on | module instance |
| Plugin settings | Per-enablement settings edited from Admin → Plugins | module settings |

Do not call an Access job role, or Spatie's `Role`, a recruitment role. Do not call a recruitment vacancy a job role. `RecruitmentRole` is the vacancy.

`docs/glossary.md` does not exist yet. Link to it from here when [#267](https://github.com/APESCIC/MyAPES-Account/issues/267) adds it. Until then, do not rename on-screen labels just to match this page. The Admin Plugins index already says Plugins; the code underneath still says module.

## Rename map

| In the code today | Means | Target name |
| --- | --- | --- |
| `SubCoreDefinition`, `sub_core_key`, `service.selected` | Organisation area | Module, module slug |
| `ModuleDefinition`, `module_key`, `module.available` | Feature | Plugin, plugin slug |
| `ModuleInstanceDefinition` (`apes-cic:tickets`) | Area × feature | Plugin enablement |
| `module_installations`, `module_settings` | Rows for that pair | Same tables until a child renames them |
| `FirstPartyModuleRegistry` | Hard-coded matrix | Module registry + plugin manifests |
| `/admin/modules` | Plugins screen | Keep the URL ([URLs](#urls)) |

## Folder layout (proposal for #281)

Approved by [ADR 0001](adr/0001-core-modules-plugins.md). Not created yet.

```
app/Core/...                         App\Core\
modules/<slug>/src/...               Modules\<Studly>\
modules/<slug>/{routes,resources/views,lang,config,database/migrations,tests}
plugins/<slug>/src/...               Plugins\<Studly>\
plugins/<slug>/{routes,resources/views,lang,config,database/migrations,database/factories,tests}
```

Examples: `modules/apes-cic` → `Modules\ApesCic`, `plugins/pet-profiles` → `Plugins\PetProfiles`. One service provider per module and per plugin, discovered from the registries. Core providers stay in `bootstrap/providers.php`. `App\` stays for the Laravel skeleton until a class actually moves.

Before any model moves, Core calls `Relation::enforceMorphMap()` with stable aliases (`user`, `support_ticket`, `case`, `pet_profile`, `consultation`, `recruitment_role`, `recruitment_application`, …) and rewrites stored FQCN values. That is [#281](https://github.com/APESCIC/MyAPES-Account/issues/281). The inventory lists `auditable_type`, `attachable_type`, `notifiable_type`, and Spatie `model_type`.

## Permissions

Stored names stay. See [ADR 0001](adr/0001-core-modules-plugins.md) for the full rule.

- Plugin abilities are already `{module}.{plugin}.{ability}` (the old `{subCore}.{module}.{ability}`). Example: `apes-cic.recruitment.review-applications`. Leave them.
- Core abilities stay `admin.*` and `superadmin.access`. Do not rename them to `core.*` in this epic.
- There is no module-only permission and no `plugin.*` global permission today. Do not add a second name for an ability that already exists.

A rename, if one is ever required, updates the `permissions` row in place so existing role, user, and group grants keep the same id ([#292](https://github.com/APESCIC/MyAPES-Account/issues/292)).

## URLs

Live URLs stay. Packages may own the route files later; the URI and the route name do not change unless [#293](https://github.com/APESCIC/MyAPES-Account/issues/293) adds a 301 from the old URL.

| Kind | Pattern today | Decision |
| --- | --- | --- |
| Core | `/`, `/admin/*`, `/profile`, `/dashboard`, auth, legal, `/change-log`, `/healthz` | Keep |
| Module hub | `/apes-cic`, `/shelter`, `/petcare` | Keep prefixes. Do not switch them to the slugs |
| Plugin inside a module | `/{prefix}/tickets`, `/cases`, `/pets`, `/consultations`, `/apes-cic/recruitment` | Keep. `/pets` stays the pet-profiles path |
| Public plugin | `/recruitment`, `/recruitment/applications*` | Keep. No module prefix |
| Admin plugin settings | `/admin/modules/{subCoreKey}/{moduleKey}/settings` | Keep |
| Legacy | `/superadmin/*` → Admin | Keep the redirects |

## What is out of this document

Behaviour changes, UX redesigns, RBAC redesign, renaming `shelter_cases` for tidiness, and string extraction ([#264](https://github.com/APESCIC/MyAPES-Account/issues/264)). Those stay in their own issues.
