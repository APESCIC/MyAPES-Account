# Architecture inventory

Generated from `php artisan route:list --json --except-vendor` on v0.36.0 (`8b9882d`, 126 routes; v0.36.1 did not change `routes/web.php`) and a walk of `app/`, migrations, views, tests, and config, then updated for the v0.36.1 staff navigation view. Reviewed against [ADR 0001](adr/0001-core-modules-plugins.md) and [architecture.md](architecture.md).

This is a map for later children of [#278](https://github.com/APESCIC/MyAPES-Account/issues/278). It does not move files. Target paths are the [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) layout. Live URLs and permission strings stay ([ADR 0001](adr/0001-core-modules-plugins.md)).

There is no `app/Livewire` directory and no Livewire dependency.

## How to read a row

- **Layer** is `Core`, `Module:<slug>`, or `Plugin:<slug>`. A plugin row that names a module is that plugin running inside that module, not a second owner.
- **Target** is where [#281](https://github.com/APESCIC/MyAPES-Account/issues/281) and the move children should put it. Historical migrations stay in `database/migrations` when the note says so, because `ForwardOnlyDatabaseMigrationsTest` discovers that folder.
- Each `app/` class, migration, Blade file, named route, and permission appears once.

## What does not fit cleanly

| Coupling | Why it is awkward | Child |
| --- | --- | --- |
| `App\Http\Controllers\ApesCic\TicketController` | One class and `resources/views/apes-cic/tickets/*` serve `/apes-cic/tickets`, `/shelter/tickets`, and `/petcare/tickets`. | #289 |
| `App\Models\ShelterCase` | Table `shelter_cases` stores Shelter cases and APES CIC cases. The area is `sub_core_key`. | #289 |
| `Shelter\CaseController` → `ApesCic\CaseUpdateController` | Shelter case updates post to the APES CIC controller. | #289 |
| Two pet profile controllers | `Shelter\PetProfileController` and `PetCare\PetProfileController` are parallel copies over one `PetProfile` model. | #291 |
| `AppServiceProvider` | Registers every plugin policy and calls `ModuleSettingsService::recruitmentPublicBoardEnabled()`. | #282 |
| `ModuleSettingsRegistry::configurableModuleKeys()` | Defaults the settings module to `apes-cic`. | #288 |
| `ModuleSettingsDefaults::ticketsForApesCic()` | Ticket defaults named for one module. | #288 |
| Route prefix ≠ slug | `shelter-rescue` is `/shelter` (`shelter.*`). `pet-care-clinic` is `/petcare` (`petcare.*`). Pet profiles are `/pets`. | #293 (keep the URLs) |
| Public recruitment | `/recruitment` is not under `/apes-cic`, but enablement is `apes-cic` + `recruitment`. Staff manage is `/apes-cic/recruitment`. | #290 |
| Admin copy vs code | `/admin/modules` is labelled Plugins. Code, tables, and permissions still say module. | Docs now; code in #283/#288 |
| `PRODUCT.md` said Services | Organisation areas were Services. The layer name is Module. UI labels wait for #267. | This PR updates PRODUCT.md |
| Shelter and Pet Care ticket routes | `shelter.tickets.*` and `petcare.tickets.*` have no `DELETE` route. The code-owned `delete` permission still exists. APES CIC tickets do have `apes-cic.tickets.destroy`. | #289 |
| Staff Recruitment IA (v0.36.1) | Staff manage URLs are still `/apes-cic/recruitment*`. v0.36.1 added a Recruit manage primary and a Roles / Applications submenu in the views (`resources/views/apes-cic/recruitment/_navigation.blade.php`). It did not add routes. | #290 |

### Stored class names ([#281](https://github.com/APESCIC/MyAPES-Account/issues/281))

No morph map is enforced. Moving a model without aliases breaks existing rows.

| Column | Stores | Models seen in code |
| --- | --- | --- |
| `audit_logs.auditable_type` | `getMorphClass()` via `AuditLogger` | Any auditable model, including `User` and plugin models that write audit events |
| `support_attachments.attachable_type` | `getMorphClass()` via `SupportAttachmentService` | `SupportTicket`, `ShelterCase` (`morphMany`) |
| `notifications.notifiable_type` | Laravel database notifications | `User` |
| `notifications.type` | Notification class FQCN | `TicketUpdatedNotification`, `ApesCicCaseUpdatedNotification`, `ShelterCaseUpdatedNotification`, `ConsultationUpdatedNotification`, `PendingFirstLoginChaseNotification` |
| Spatie `model_has_roles.model_type`, `model_has_permissions.model_type` | FQCN | `User` |

Suggested aliases, to be applied before any namespace change: `user`, `support_ticket`, `support_ticket_message`, `case`, `case_update`, `pet_profile`, `consultation`, `recruitment_role`, `recruitment_application`.

## Shipped matrix

From `FirstPartyModuleRegistry` on this commit. Nine of fifteen area × feature pairs are shipped. Permissions exist only for shipped pairs (65 names).

| Module \ Plugin | tickets | cases | pet-profiles | consultations | recruitment |
| --- | --- | --- | --- | --- | --- |
| `apes-cic` | shipped | shipped | not shipped | not shipped | shipped |
| `shelter-rescue` | shipped | shipped (depends on pet-profiles) | shipped | not shipped | not shipped |
| `pet-care-clinic` | shipped | not shipped | shipped | shipped (depends on pet-profiles) | not shipped |

## Controllers

30 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Http/Controllers/Admin/AdminAccessController.php | Core | app/Core/Http/Controllers/Admin/AdminAccessController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/AdminGroupController.php | Core | app/Core/Http/Controllers/Admin/AdminGroupController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/AdminMaintenanceController.php | Core | app/Core/Http/Controllers/Admin/AdminMaintenanceController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/AdminModuleController.php | Core | app/Core/Admin/PluginAdministrationController.php | Admin → Plugins. URL and class still say module. Settings pages are per plugin enablement. #288. |
| app/Http/Controllers/Admin/AdminPermissionController.php | Core | app/Core/Http/Controllers/Admin/AdminPermissionController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/AdminRoleController.php | Core | app/Core/Http/Controllers/Admin/AdminRoleController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/AdminUserController.php | Core | app/Core/Http/Controllers/Admin/AdminUserController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Admin/StaffAdminController.php | Core | app/Core/Http/Controllers/Admin/StaffAdminController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/ApesCic/CaseController.php | Plugin:cases | plugins/cases/src/Http/Controllers/CaseController.php | APES CIC cases. Shares ShelterCase with Shelter. #289. |
| app/Http/Controllers/ApesCic/CaseUpdateController.php | Plugin:cases | plugins/cases/src/Http/Controllers/CaseUpdateController.php | Also mounted on /shelter/cases/{case}/updates. #289. |
| app/Http/Controllers/ApesCic/RecruitmentApplicationController.php | Plugin:recruitment | plugins/recruitment/src/Http/Controllers/RecruitmentApplicationController.php | Staff review on module apes-cic. #290. |
| app/Http/Controllers/ApesCic/RecruitmentRoleController.php | Plugin:recruitment | plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php | Staff CRUD on module apes-cic. RecruitmentRole is not Spatie Role. #290. Moves after v0.36 staff IA. |
| app/Http/Controllers/ApesCic/TicketController.php | Plugin:tickets | plugins/tickets/src/Http/Controllers/TicketController.php | Serves /apes-cic, /shelter, and /petcare tickets, and renders apes-cic.tickets views for all three. Split the hard-coded area in #289. |
| app/Http/Controllers/Auth/OidcAuthController.php | Core | app/Core/Http/Controllers/Auth/OidcAuthController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Auth/PublicAuthController.php | Core | app/Core/Http/Controllers/Auth/PublicAuthController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/Auth/PublicPasswordResetController.php | Core | app/Core/Http/Controllers/Auth/PublicPasswordResetController.php | Admin shell or auth. Permission middleware stays admin.* / superadmin.access. |
| app/Http/Controllers/ChangeLogController.php | Core | app/Core/Http/Controllers/ChangeLogController.php | Account, profile, dashboard, health, or change log. |
| app/Http/Controllers/Controller.php | Core | app/Core/Http/Controllers/Controller.php | Laravel base controller. Stays with the skeleton until callers move. |
| app/Http/Controllers/DashboardController.php | Core | app/Core/Http/Controllers/DashboardController.php | Account, profile, dashboard, health, or change log. |
| app/Http/Controllers/HealthController.php | Core | app/Core/Http/Controllers/HealthController.php | Account, profile, dashboard, health, or change log. |
| app/Http/Controllers/OnboardingController.php | Core | app/Core/Http/Controllers/OnboardingController.php | Account, profile, dashboard, health, or change log. |
| app/Http/Controllers/PetCare/ConsultationController.php | Plugin:consultations | plugins/consultations/src/Http/Controllers/ConsultationController.php | Module pet-care-clinic. Depends on pet profiles. #290. |
| app/Http/Controllers/PetCare/PetProfileController.php | Plugin:pet-profiles | plugins/pet-profiles/src/Http/Controllers/PetProfileController.php | Module pet-care-clinic. Two controllers until #291 folds them behind ModuleContext. |
| app/Http/Controllers/ProfileController.php | Core | app/Core/Http/Controllers/ProfileController.php | Account, profile, dashboard, health, or change log. |
| app/Http/Controllers/PublicRecruitmentApplicationController.php | Plugin:recruitment | plugins/recruitment/src/Http/Controllers/PublicRecruitmentApplicationController.php | Public apply and my applications. #290. |
| app/Http/Controllers/RecruitmentBoardController.php | Plugin:recruitment | plugins/recruitment/src/Http/Controllers/RecruitmentBoardController.php | Public /recruitment. No module prefix. Gated by apes-cic recruitment enablement. #290. |
| app/Http/Controllers/Shelter/CaseController.php | Plugin:cases | plugins/cases/src/Http/Controllers/ShelterCaseController.php | Shelter cases. Reuses ApesCic CaseUpdateController. #289. |
| app/Http/Controllers/Shelter/PetProfileController.php | Plugin:pet-profiles | plugins/pet-profiles/src/Http/Controllers/PetProfileController.php | Module shelter-rescue. Sibling of the Pet Care controller. #291. |
| app/Http/Controllers/SubCoreController.php | Core | app/Core/Modules/ModuleHubController.php | One hub controller for all three modules (sub-cores.show). Becomes per-module hubs in #284–#286. Name is the old vocabulary. |
| app/Http/Controllers/SupportAttachmentController.php | Core | app/Core/Attachments/SupportAttachmentController.php | Shared attachments for tickets and cases. Stays Core. |

## Models

28 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Models/AuditLog.php | Core | app/Core/Accounts/AuditLog.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/AuthorizationState.php | Core | app/Core/Accounts/AuthorizationState.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/CaseUpdate.php | Plugin:cases | plugins/cases/src/Models/CaseUpdate.php | #289. |
| app/Models/ContactConsentEvent.php | Core | app/Core/Accounts/ContactConsentEvent.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/DirectoryGroup.php | Core | app/Core/Accounts/DirectoryGroup.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/DirectoryGroupRoleMapping.php | Core | app/Core/Accounts/DirectoryGroupRoleMapping.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/DirectorySyncRun.php | Core | app/Core/Accounts/DirectorySyncRun.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/MaintenanceWindow.php | Core | app/Core/Accounts/MaintenanceWindow.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/ModuleInstallation.php | Core | app/Core/Extensions/PluginEnablement.php | Table module_installations keyed (sub_core_key, module_key). Core enablement store. Rename only with a child decision. |
| app/Models/ModuleSetting.php | Core | app/Core/Extensions/PluginSetting.php | Table module_settings. Same key pair. #288. |
| app/Models/OidcLinkIntent.php | Core | app/Core/Accounts/OidcLinkIntent.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/Permission.php | Core | app/Core/Accounts/Permission.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/PermissionSource.php | Core | app/Core/Accounts/PermissionSource.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/PetCareConsultation.php | Plugin:consultations | plugins/consultations/src/Models/Consultation.php | Table pet_care_consultations. #290. |
| app/Models/PetProfile.php | Plugin:pet-profiles | plugins/pet-profiles/src/Models/PetProfile.php | Shared by Pet Care Clinic and Shelter. #291. |
| app/Models/RecruitmentApplication.php | Plugin:recruitment | plugins/recruitment/src/Models/RecruitmentApplication.php | #290. |
| app/Models/RecruitmentRole.php | Plugin:recruitment | plugins/recruitment/src/Models/RecruitmentRole.php | Vacancy. Never Spatie Role. #290. |
| app/Models/Role.php | Core | app/Core/Accounts/Role.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/RoleSource.php | Core | app/Core/Accounts/RoleSource.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/ShelterCase.php | Plugin:cases | plugins/cases/src/Models/CaseRecord.php | Table shelter_cases. Also stores APES CIC cases via sub_core_key. Do not rename the table for tidiness. #289. |
| app/Models/StaffProfile.php | Core | app/Core/Accounts/StaffProfile.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/SupportAttachment.php | Core | app/Core/Attachments/SupportAttachment.php | Polymorphic attachable_type stores the FQCN. Used by tickets and cases. #281 morph map. |
| app/Models/SupportTicket.php | Plugin:tickets | plugins/tickets/src/Models/SupportTicket.php | morphMany SupportAttachment. #289. Morph alias before move (#281). |
| app/Models/SupportTicketMessage.php | Plugin:tickets | plugins/tickets/src/Models/SupportTicketMessage.php | #289. |
| app/Models/User.php | Core | app/Core/Accounts/User.php | Spatie model_type and notifications.notifiable_type store this FQCN. #281. |
| app/Models/UserContactPreference.php | Core | app/Core/Accounts/UserContactPreference.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/UserProfile.php | Core | app/Core/Accounts/UserProfile.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |
| app/Models/UserServiceSelection.php | Core | app/Core/Accounts/UserServiceSelection.php | Account, access, audit, maintenance, or consent model. Morph users before any namespace move (#281). |

## Policies

6 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Policies/PetCareConsultationPolicy.php | Plugin:consultations | plugins/consultations/src/Policies/ConsultationPolicy.php | #290. |
| app/Policies/PetProfilePolicy.php | Plugin:pet-profiles | plugins/pet-profiles/src/Policies/PetProfilePolicy.php | #291. |
| app/Policies/RecruitmentApplicationPolicy.php | Plugin:recruitment | plugins/recruitment/src/Policies/RecruitmentApplicationPolicy.php | #290. |
| app/Policies/RecruitmentRolePolicy.php | Plugin:recruitment | plugins/recruitment/src/Policies/RecruitmentRolePolicy.php | #290. |
| app/Policies/ShelterCasePolicy.php | Plugin:cases | plugins/cases/src/Policies/CasePolicy.php | Covers APES CIC and Shelter cases. #289. |
| app/Policies/SupportTicketPolicy.php | Plugin:tickets | plugins/tickets/src/Policies/SupportTicketPolicy.php | Registered from AppServiceProvider. #289. |

## Notifications

5 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Notifications/ApesCicCaseUpdatedNotification.php | Plugin:cases | plugins/cases/src/Notifications/ApesCicCaseUpdatedNotification.php | Audit/event flavour is area-specific. #289. |
| app/Notifications/ConsultationUpdatedNotification.php | Plugin:consultations | plugins/consultations/src/Notifications/ConsultationUpdatedNotification.php | #290. |
| app/Notifications/PendingFirstLoginChaseNotification.php | Core | app/Core/Notifications/PendingFirstLoginChaseNotification.php | Staff first-login chase. Not a plugin. |
| app/Notifications/ShelterCaseUpdatedNotification.php | Plugin:cases | plugins/cases/src/Notifications/ShelterCaseUpdatedNotification.php | #289. |
| app/Notifications/TicketUpdatedNotification.php | Plugin:tickets | plugins/tickets/src/Notifications/TicketUpdatedNotification.php | notifications.type stores the FQCN. #289 / #281. |

## HTTP middleware and other HTTP

8 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Http/Cookies/OidcReauthenticationCookie.php | Core | app/Core/Http/Cookies/OidcReauthenticationCookie.php | Core HTTP support. |
| app/Http/Middleware/AuditAdminAuthorizationDenial.php | Core | app/Core/Http/Middleware/AuditAdminAuthorizationDenial.php | Core HTTP middleware. |
| app/Http/Middleware/EnsureAccountReady.php | Core | app/Core/Http/Middleware/EnsureAccountReady.php | Core HTTP middleware. |
| app/Http/Middleware/EnsureAuthorizationContext.php | Core | app/Core/Http/Middleware/EnsureAuthorizationContext.php | Core HTTP middleware. |
| app/Http/Middleware/EnsureMaintenanceRecoveryAccess.php | Core | app/Core/Http/Middleware/EnsureMaintenanceRecoveryAccess.php | Core HTTP middleware. |
| app/Http/Middleware/EnsureModuleAvailable.php | Core | app/Core/Http/Middleware/EnsurePluginEnabled.php | Alias module.available:{subCore},{module}. Core gate over plugin enablement. #288. |
| app/Http/Middleware/EnsureServiceSelected.php | Core | app/Core/Http/Middleware/EnsureModuleSelected.php | Alias service.selected:{subCore}. Old word service means module. #284–#286. |
| app/Http/Middleware/RevalidateDirectoryAccess.php | Core | app/Core/Http/Middleware/RevalidateDirectoryAccess.php | Core HTTP middleware. |

## Providers

2 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Providers/AppServiceProvider.php | Core | app/Providers/AppServiceProvider.php | Registers every plugin policy and reads recruitment board settings. Coupling for #282. Stays the skeleton provider. |
| app/Providers/ModuleServiceProvider.php | Core | app/Providers/ModuleServiceProvider.php | Binds FirstPartyModuleRegistry, nav composer, rate limiters. Splits into discovered providers in #283/#287. |

## Jobs and console

15 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Console/Commands/AccountsCheck.php | Core | app/Core/Console/Commands/AccountsCheck.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/AccountsPreflight.php | Core | app/Core/Console/Commands/AccountsPreflight.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/AuthorizationCheck.php | Core | app/Core/Console/Commands/AuthorizationCheck.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/AuthorizationPreflight.php | Core | app/Core/Console/Commands/AuthorizationPreflight.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/AuthorizationSync.php | Core | app/Core/Console/Commands/AuthorizationSync.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/DirectorySync.php | Core | app/Core/Console/Commands/DirectorySync.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/ModulesCheck.php | Core | app/Core/Console/Commands/ModulesCheck.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/ModulesPreflight.php | Core | app/Core/Console/Commands/ModulesPreflight.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/ModulesRollbackCheck.php | Core | app/Core/Console/Commands/ModulesRollbackCheck.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/ModulesSync.php | Core | app/Core/Console/Commands/ModulesSync.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/MyapesAuthCheck.php | Core | app/Core/Console/Commands/MyapesAuthCheck.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/PrepareChangeLog.php | Core | app/Core/Console/Commands/PrepareChangeLog.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/SyncAccessCompatibility.php | Core | app/Core/Console/Commands/SyncAccessCompatibility.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Console/Commands/ValidateChangeLog.php | Core | app/Core/Console/Commands/ValidateChangeLog.php | Artisan command. myapes:modules-* stays the enablement CLI until #295 renames the signature with an alias. |
| app/Jobs/RunDirectorySync.php | Core | app/Core/Jobs/RunDirectorySync.php | Core job. |

## Contracts

10 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Contracts/MaintenanceModeGateway.php | Core | app/Core/Contracts/MaintenanceModeGateway.php | Core contract. |
| app/Contracts/ModuleActiveRecordDetector.php | Core | app/Core/Contracts/ModuleActiveRecordDetector.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleAggregateSummaryProvider.php | Core | app/Core/Contracts/ModuleAggregateSummaryProvider.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleAnalyticsProvider.php | Core | app/Core/Contracts/ModuleAnalyticsProvider.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleAttentionProvider.php | Core | app/Core/Contracts/ModuleAttentionProvider.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleLifecycleManager.php | Core | app/Core/Contracts/ModuleLifecycleManager.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleNavigationProvider.php | Core | app/Core/Contracts/ModuleNavigationProvider.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleRecentActivityProvider.php | Core | app/Core/Contracts/ModuleRecentActivityProvider.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/ModuleRegistry.php | Core | app/Core/Contracts/ModuleRegistry.php | Extension contract implemented by plugin providers today. Moves with the registry split (#283/#287), still owned by Core. |
| app/Contracts/OidcIdentityProvider.php | Core | app/Core/Contracts/OidcIdentityProvider.php | Core contract. |

## Registry types (`app/Modules` definitions)

17 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Modules/FirstPartyModuleRegistry.php | Core | app/Core/Extensions/FirstPartyModuleRegistry.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleAbilityDefinition.php | Core | app/Core/Extensions/ModuleAbilityDefinition.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleAnalyticsSnapshot.php | Core | app/Core/Extensions/ModuleAnalyticsSnapshot.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleAttentionItem.php | Core | app/Core/Extensions/ModuleAttentionItem.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleCodeStatus.php | Core | app/Core/Extensions/ModuleCodeStatus.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleDefinition.php | Core | app/Core/Extensions/ModuleDefinition.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleDependency.php | Core | app/Core/Extensions/ModuleDependency.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleInstanceDefinition.php | Core | app/Core/Extensions/ModuleInstanceDefinition.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleNavigationDefinition.php | Core | app/Core/Extensions/ModuleNavigationDefinition.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleNavigationItem.php | Core | app/Core/Extensions/ModuleNavigationItem.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModulePermissionDescriptor.php | Core | app/Core/Extensions/ModulePermissionDescriptor.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleRecentActivityItem.php | Core | app/Core/Extensions/ModuleRecentActivityItem.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleSettingsDescriptor.php | Core | app/Core/Extensions/ModuleSettingsDescriptor.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleSummary.php | Core | app/Core/Extensions/ModuleSummary.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/ModuleSummaryGroup.php | Core | app/Core/Extensions/ModuleSummaryGroup.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/SubCoreDefinition.php | Core | app/Core/Extensions/SubCoreDefinition.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |
| app/Modules/SubCoreNavigation.php | Core | app/Core/Extensions/SubCoreNavigation.php | Registry type. SubCore* becomes Module*, Module* (the feature) becomes Plugin*, module instance becomes plugin enablement. #283/#287. Do not rename in place without the ADR map. |

## Dashboard providers (`app/Modules/{Activity,Analytics,Attention,Detectors,Summaries}`)

20 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Modules/Activity/CaseRecentActivityProvider.php | Plugin:cases | plugins/cases/src/Dashboard/CaseRecentActivityProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Activity/PetCareConsultationRecentActivityProvider.php | Plugin:consultations | plugins/consultations/src/Dashboard/PetCareConsultationRecentActivityProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Activity/PetProfileRecentActivityProvider.php | Plugin:pet-profiles | plugins/pet-profiles/src/Dashboard/PetProfileRecentActivityProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #291. |
| app/Modules/Activity/SupportTicketRecentActivityProvider.php | Plugin:tickets | plugins/tickets/src/Dashboard/SupportTicketRecentActivityProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Analytics/CaseAnalyticsProvider.php | Plugin:cases | plugins/cases/src/Dashboard/CaseAnalyticsProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Analytics/PetCareConsultationAnalyticsProvider.php | Plugin:consultations | plugins/consultations/src/Dashboard/PetCareConsultationAnalyticsProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Analytics/PetProfileAnalyticsProvider.php | Plugin:pet-profiles | plugins/pet-profiles/src/Dashboard/PetProfileAnalyticsProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #291. |
| app/Modules/Analytics/SupportTicketAnalyticsProvider.php | Plugin:tickets | plugins/tickets/src/Dashboard/SupportTicketAnalyticsProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Attention/CaseAttentionProvider.php | Plugin:cases | plugins/cases/src/Dashboard/CaseAttentionProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Attention/ConsultationAttentionProvider.php | Plugin:consultations | plugins/consultations/src/Dashboard/ConsultationAttentionProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Attention/SupportTicketAttentionProvider.php | Plugin:tickets | plugins/tickets/src/Dashboard/SupportTicketAttentionProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Detectors/PetCareConsultationActiveRecordDetector.php | Plugin:consultations | plugins/consultations/src/Dashboard/PetCareConsultationActiveRecordDetector.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Detectors/PetProfileActiveRecordDetector.php | Plugin:pet-profiles | plugins/pet-profiles/src/Dashboard/PetProfileActiveRecordDetector.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #291. |
| app/Modules/Detectors/RecruitmentActiveRecordDetector.php | Plugin:recruitment | plugins/recruitment/src/Dashboard/RecruitmentActiveRecordDetector.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Detectors/ShelterCaseActiveRecordDetector.php | Plugin:cases | plugins/cases/src/Dashboard/ShelterCaseActiveRecordDetector.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Detectors/SupportTicketActiveRecordDetector.php | Plugin:tickets | plugins/tickets/src/Dashboard/SupportTicketActiveRecordDetector.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Summaries/PetCareConsultationSummaryProvider.php | Plugin:consultations | plugins/consultations/src/Dashboard/PetCareConsultationSummaryProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #290. |
| app/Modules/Summaries/PetProfileSummaryProvider.php | Plugin:pet-profiles | plugins/pet-profiles/src/Dashboard/PetProfileSummaryProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #291. |
| app/Modules/Summaries/ShelterCaseSummaryProvider.php | Plugin:cases | plugins/cases/src/Dashboard/ShelterCaseSummaryProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |
| app/Modules/Summaries/SupportTicketSummaryProvider.php | Plugin:tickets | plugins/tickets/src/Dashboard/SupportTicketSummaryProvider.php | Dashboard provider hard-wired from FirstPartyModuleRegistry. #289. |

## Services, support, and rules

103 files.

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| app/Auth/DirectoryAuthorizationResult.php | Core | app/Core/Auth/DirectoryAuthorizationResult.php | Auth or account exception/value. Core. |
| app/Auth/DirectoryUserProfile.php | Core | app/Core/Auth/DirectoryUserProfile.php | Auth or account exception/value. Core. |
| app/Auth/OidcFlow.php | Core | app/Core/Auth/OidcFlow.php | Auth or account exception/value. Core. |
| app/Auth/OidcIdentity.php | Core | app/Core/Auth/OidcIdentity.php | Auth or account exception/value. Core. |
| app/Exceptions/AuthReadinessException.php | Core | app/Core/Exceptions/AuthReadinessException.php | Auth or account exception/value. Core. |
| app/Exceptions/AuthorizationLifecycleException.php | Core | app/Core/Exceptions/AuthorizationLifecycleException.php | Auth or account exception/value. Core. |
| app/Exceptions/AuthorizationMutationDenied.php | Core | app/Core/Exceptions/AuthorizationMutationDenied.php | Auth or account exception/value. Core. |
| app/Exceptions/DirectoryIdentityNotFound.php | Core | app/Core/Exceptions/DirectoryIdentityNotFound.php | Auth or account exception/value. Core. |
| app/Exceptions/DirectorySyncInProgress.php | Core | app/Core/Exceptions/DirectorySyncInProgress.php | Auth or account exception/value. Core. |
| app/Exceptions/DirectoryUnavailable.php | Core | app/Core/Exceptions/DirectoryUnavailable.php | Auth or account exception/value. Core. |
| app/Exceptions/MaintenanceTransitionException.php | Core | app/Core/Exceptions/MaintenanceTransitionException.php | Auth or account exception/value. Core. |
| app/Exceptions/ModuleLifecycleException.php | Core | app/Core/Exceptions/ModuleLifecycleException.php | Thrown by the plugin enablement lifecycle. Still Core. #283. |
| app/Exceptions/OidcProviderException.php | Core | app/Core/Exceptions/OidcProviderException.php | Auth or account exception/value. Core. |
| app/Rules/EligibleStaffAssignee.php | Core | app/Core/Access/EligibleStaffAssignee.php | Shared assignee rule used by tickets, cases, and consultations. Stays Core so plugins do not copy it. |
| app/Rules/EligibleTicketOwner.php | Plugin:tickets | plugins/tickets/src/Rules/EligibleTicketOwner.php | #289. |
| app/Rules/UkDateTimeFormat.php | Core | app/Core/Support/UkDateTimeFormat.php | Shared validation. |
| app/Services/AccountLifecycleReadinessChecker.php | Core | app/Core/Services/AccountLifecycleReadinessChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AdminAnalyticsAggregator.php | Core | app/Core/Services/AdminAnalyticsAggregator.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ApplicationAuthorizationGate.php | Core | app/Core/Services/ApplicationAuthorizationGate.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AssignmentAuthorization.php | Core | app/Core/Services/AssignmentAuthorization.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuditLogger.php | Core | app/Core/Services/AuditLogger.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthReadinessChecker.php | Core | app/Core/Services/AuthReadinessChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationAccountSynchronizer.php | Core | app/Core/Services/AuthorizationAccountSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationActivationSynchronizer.php | Core | app/Core/Services/AuthorizationActivationSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationDirectPermissionMaterializer.php | Core | app/Core/Services/AuthorizationDirectPermissionMaterializer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationDirectPermissionPolicy.php | Core | app/Core/Services/AuthorizationDirectPermissionPolicy.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationIntegrityChecker.php | Core | app/Core/Services/AuthorizationIntegrityChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationMetadataSynchronizer.php | Core | app/Core/Services/AuthorizationMetadataSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationMutationService.php | Core | app/Core/Services/AuthorizationMutationService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationPermissionSynchronizer.php | Core | app/Core/Services/AuthorizationPermissionSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationPhaseBSchemaInspector.php | Core | app/Core/Services/AuthorizationPhaseBSchemaInspector.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationPreflightChecker.php | Core | app/Core/Services/AuthorizationPreflightChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationProfile.php | Core | app/Core/Services/AuthorizationProfile.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationRoleManagementService.php | Core | app/Core/Services/AuthorizationRoleManagementService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/AuthorizationRoleMaterializer.php | Core | app/Core/Services/AuthorizationRoleMaterializer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/CaseCategoryResolver.php | Plugin:cases | plugins/cases/src/CaseCategoryResolver.php | #289. |
| app/Services/ContactPreferenceUpdater.php | Core | app/Core/Services/ContactPreferenceUpdater.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DatabaseModuleLifecycleManager.php | Core | app/Core/Services/DatabaseModuleLifecycleManager.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DirectoryCatalogueSynchronizer.php | Core | app/Core/Services/DirectoryCatalogueSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DirectoryGroupMappingService.php | Core | app/Core/Services/DirectoryGroupMappingService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DirectoryRoleSynchronizer.php | Core | app/Core/Services/DirectoryRoleSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DirectorySyncTerminalFailureRecorder.php | Core | app/Core/Services/DirectorySyncTerminalFailureRecorder.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/DirectoryUserSynchronizer.php | Core | app/Core/Services/DirectoryUserSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/JumbojettOidcIdentityProvider.php | Core | app/Core/Services/JumbojettOidcIdentityProvider.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LaravelMaintenanceModeGateway.php | Core | app/Core/Services/LaravelMaintenanceModeGateway.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LaravelOpenIdConnectClient.php | Core | app/Core/Services/LaravelOpenIdConnectClient.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LdapGroupResolver.php | Core | app/Core/Services/LdapGroupResolver.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LdapUserResolver.php | Core | app/Core/Services/LdapUserResolver.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LegacyAccessCompatibilityAdapter.php | Core | app/Core/Services/LegacyAccessCompatibilityAdapter.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/LocalPublicPasswordResetService.php | Core | app/Core/Services/LocalPublicPasswordResetService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/MaintenanceLifecycleManager.php | Core | app/Core/Services/MaintenanceLifecycleManager.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/MaintenanceResponseFactory.php | Core | app/Core/Services/MaintenanceResponseFactory.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ManualDirectorySyncQueueResolver.php | Core | app/Core/Services/ManualDirectorySyncQueueResolver.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleAdministrationCatalogue.php | Core | app/Core/Admin/PluginAdministrationCatalogue.php | Admin Plugins index. #288. |
| app/Services/ModuleCatalogueProjection.php | Core | app/Core/Services/ModuleCatalogueProjection.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleDashboardAttentionService.php | Core | app/Core/Services/ModuleDashboardAttentionService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleDashboardSummaryService.php | Core | app/Core/Services/ModuleDashboardSummaryService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleInstallationSynchronizer.php | Core | app/Core/Services/ModuleInstallationSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleInstanceLock.php | Core | app/Core/Services/ModuleInstanceLock.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleIntegrityChecker.php | Core | app/Core/Services/ModuleIntegrityChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModulePreflightChecker.php | Core | app/Core/Services/ModulePreflightChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleProjectionCache.php | Core | app/Core/Services/ModuleProjectionCache.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleRegistryValidator.php | Core | app/Core/Services/ModuleRegistryValidator.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleRollbackCompatibilityChecker.php | Core | app/Core/Services/ModuleRollbackCompatibilityChecker.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleRouteContext.php | Core | app/Core/Extensions/ModuleContext.php | Today reads route defaults subCoreKey/moduleKey. Becomes the request ModuleContext. #283. |
| app/Services/ModuleSettingsRegistry.php | Core | app/Core/Extensions/PluginSettingsRegistry.php | configurableModuleKeys() defaults to apes-cic. Docblock already says per-plugin settings. #288. |
| app/Services/ModuleSettingsService.php | Core | app/Core/Extensions/PluginSettingsService.php | AppServiceProvider reads recruitmentPublicBoardEnabled() from here. #282. |
| app/Services/ModuleSettingsSynchronizer.php | Core | app/Core/Services/ModuleSettingsSynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ModuleState.php | Core | app/Core/Services/ModuleState.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/OidcDiscoveryValidator.php | Core | app/Core/Services/OidcDiscoveryValidator.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/PendingFirstLoginChaseService.php | Core | app/Core/Services/PendingFirstLoginChaseService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/PetProfilePhotoResponder.php | Plugin:pet-profiles | plugins/pet-profiles/src/PetProfilePhotoResponder.php | #291. |
| app/Services/PrivilegedMutationAuthorizer.php | Core | app/Core/Services/PrivilegedMutationAuthorizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/RegistryModuleNavigationProvider.php | Core | app/Core/Services/RegistryModuleNavigationProvider.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/SecureUploadService.php | Core | app/Core/Services/SecureUploadService.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/ServiceEntitlement.php | Core | app/Core/Services/ServiceEntitlement.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/SessionAuthorizationContext.php | Core | app/Core/Services/SessionAuthorizationContext.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/StaffProfileDirectorySynchronizer.php | Core | app/Core/Services/StaffProfileDirectorySynchronizer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/StaffProfilePhotoResponder.php | Core | app/Core/Services/StaffProfilePhotoResponder.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/SupportAttachmentService.php | Core | app/Core/Attachments/SupportAttachmentService.php | Writes attachable_type from getMorphClass(). #281. |
| app/Services/TicketCategoryResolver.php | Plugin:tickets | plugins/tickets/src/TicketCategoryResolver.php | #289. |
| app/Services/TicketServiceConfiguration.php | Plugin:tickets | plugins/tickets/src/TicketServiceConfiguration.php | Per-module ticket category config. #289. |
| app/Services/TicketServiceDefinition.php | Core | app/Core/Services/TicketServiceDefinition.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Services/UkPhoneNumber.php | Core | app/Core/Services/UkPhoneNumber.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/AccessCompatibilityDatabaseGuard.php | Core | app/Core/Support/AccessCompatibilityDatabaseGuard.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/AuthorizationCompatibilityDatabaseGuard.php | Core | app/Core/Support/AuthorizationCompatibilityDatabaseGuard.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/AuthorizationCutoverSchema.php | Core | app/Core/Support/AuthorizationCutoverSchema.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/ChangeLogPresenter.php | Core | app/Core/Support/ChangeLogPresenter.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/DefaultJobRoles.php | Core | app/Core/Support/DefaultJobRoles.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/DirectoryGroupLabels.php | Core | app/Core/Support/DirectoryGroupLabels.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/DirectoryGroupPrefix.php | Core | app/Core/Support/DirectoryGroupPrefix.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/DirectoryImmutableMappings.php | Core | app/Core/Support/DirectoryImmutableMappings.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/DirectoryLegacyGroupAliases.php | Core | app/Core/Support/DirectoryLegacyGroupAliases.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/JobRoleCapabilityPacks.php | Core | app/Core/Access/JobRoleCapabilityPacks.php | Packs staff-module-work and module-delete expand every shipped plugin permission. #292. |
| app/Support/MascotTips.php | Core | app/Core/Support/MascotTips.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/ModuleSettingsDefaults.php | Core | app/Core/Extensions/PluginSettingsDefaults.php | ticketsForApesCic() hard-codes one module. #288. |
| app/Support/PermissionDescriptions.php | Core | app/Core/Access/PermissionDescriptions.php | Catalogue text for admin.* and plugin permissions. Grouping by layer is #292. |
| app/Support/PrivacyNotice.php | Core | app/Core/Support/PrivacyNotice.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/ReleaseHistoryPreparer.php | Core | app/Core/Support/ReleaseHistoryPreparer.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/ReleaseHistoryRepository.php | Core | app/Core/Support/ReleaseHistoryRepository.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/ReleaseHistoryValidator.php | Core | app/Core/Support/ReleaseHistoryValidator.php | Core service or support. Module* names are the enablement host, not a feature plugin. |
| app/Support/StaffPetCreateReturn.php | Plugin:pet-profiles | plugins/pet-profiles/src/Support/StaffPetCreateReturn.php | Staff create-and-return helper for both pet modules. #291. |
| app/Support/UkDateTime.php | Core | app/Core/Support/UkDateTime.php | Core service or support. Module* names are the enablement host, not a feature plugin. |

## Routes

126 routes from `php artisan route:list --json --except-vendor`. Middleware is abbreviated to the non-`web` aliases where noted in the name column's sibling. Full middleware is listed.

| Method | URI | Name | Action | Middleware | Layer | Target | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GET / HEAD | // | home | Illuminate\Routing\ViewController | web, guest | Core | routes/core.php | Public entry or health. |
| GET / HEAD | /admin | admin.index | Admin\StaffAdminController | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.analytics.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/access | admin.access.index | Admin\AdminAccessController@index | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/access/groups/{directoryGroup}/mappings | admin.access.mappings.store | Admin\AdminAccessController@storeMapping | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.group-mappings.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/access/job-roles | admin.access.job-roles.store | Admin\AdminAccessController@storeJobRole | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| DELETE | /admin/access/job-roles/{role} | admin.access.job-roles.destroy | Admin\AdminAccessController@destroyJobRole | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/access/job-roles/{role} | admin.access.job-roles.show | Admin\AdminAccessController@showJobRole | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| PUT | /admin/access/job-roles/{role} | admin.access.job-roles.update | Admin\AdminAccessController@updateJobRole | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| DELETE | /admin/access/mappings/{mapping} | admin.access.mappings.destroy | Admin\AdminAccessController@destroyMapping | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.group-mappings.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/access/sync | admin.access.sync | Admin\AdminAccessController@sync | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.group-mappings.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/groups | admin.groups.index | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.groups.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/groups/{directoryGroup} | admin.groups.show | Admin\AdminGroupController@show | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.groups.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/maintenance | admin.maintenance.index | Admin\AdminMaintenanceController@index | web, auth, authorization.context, directory.current, maintenance.recovery, admin.denial-audit, can:admin.maintenance.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/maintenance/activate | admin.maintenance.activate | Admin\AdminMaintenanceController@activate | web, auth, authorization.context, directory.current, maintenance.recovery, admin.denial-audit, can:admin.maintenance.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/maintenance/deactivate | admin.maintenance.deactivate | Admin\AdminMaintenanceController@deactivate | web, auth, authorization.context, directory.current, maintenance.recovery, admin.denial-audit, can:admin.maintenance.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/modules | admin.modules.index | Admin\AdminModuleController@index | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.view | Core | routes/core-admin.php | Admin plugin enablement and settings. URI keeps modules. Edits plugin settings for a module. |
| GET / HEAD | /admin/modules/{subCoreKey}/{moduleKey}/settings | admin.modules.settings.edit | Admin\AdminModuleController@editSettings | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.view | Core | routes/core-admin.php | Admin plugin enablement and settings. URI keeps modules. Edits plugin settings for a module. |
| PUT | /admin/modules/{subCoreKey}/{moduleKey}/settings | admin.modules.settings.update | Admin\AdminModuleController@updateSettings | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.manage | Core | routes/core-admin.php | Admin plugin enablement and settings. URI keeps modules. Edits plugin settings for a module. |
| POST | /admin/modules/{subCoreKey}/{moduleKey}/transition | admin.modules.transition | Admin\AdminModuleController@transition | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.manage | Core | routes/core-admin.php | Admin plugin enablement and settings. URI keeps modules. Edits plugin settings for a module. |
| GET / HEAD | /admin/permissions | admin.permissions.index | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.permissions.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/roles | admin.roles.index | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/roles/{role} | admin.roles.show | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.roles.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/users | admin.users.index | Admin\AdminUserController@index | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/users/{user} | admin.users.show | Admin\AdminUserController@show | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/users/{user}/password-reset | admin.users.password-reset | Admin\AdminUserController@resetLocalPassword | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/users/{user}/pending-first-login-chase | admin.users.pending-first-login-chase | Admin\AdminUserController@chasePendingFirstLogin | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| PUT | /admin/users/{user}/profile | admin.users.profile.update | Admin\AdminUserController@updateProfile | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| PUT | /admin/users/{user}/roles | admin.users.roles.update | Admin\AdminUserController@updateRoles | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /admin/users/{user}/staff-photo | admin.users.staff-photo | Admin\AdminUserController@staffPhoto | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.view | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| PUT | /admin/users/{user}/staff-profile | admin.users.staff-profile.update | Admin\AdminUserController@updateStaffProfile | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| DELETE | /admin/users/{user}/suspension | admin.users.suspension.destroy | Admin\AdminUserController@reactivate | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| POST | /admin/users/{user}/suspension | admin.users.suspension.store | Admin\AdminUserController@suspend | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.users.manage | Core | routes/core-admin.php | Admin shell. Gates stay admin.* / superadmin.access. |
| GET / HEAD | /apes-cic | apes-cic.index | SubCoreController@show | web, auth, authorization.context, directory.current, account.ready, service.selected:apes-cic | Module:apes-cic | modules/apes-cic/routes/web.php | Module hub. Prefix stays /apes-cic even though the slug is apes-cic. #284–#286. |
| GET / HEAD | /apes-cic/cases | apes-cic.cases.index | ApesCic\CaseController@index | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| POST | /apes-cic/cases | apes-cic.cases.store | ApesCic\CaseController@store | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| DELETE | /apes-cic/cases/{case} | apes-cic.cases.destroy | ApesCic\CaseController@destroy | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| GET / HEAD | /apes-cic/cases/{case} | apes-cic.cases.show | ApesCic\CaseController@show | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| PUT / PATCH | /apes-cic/cases/{case} | apes-cic.cases.update | ApesCic\CaseController@update | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| POST | /apes-cic/cases/{case}/updates | apes-cic.cases.updates.store | ApesCic\CaseUpdateController@store | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,cases, service.selected:apes-cic | Plugin:cases (module apes-cic) | plugins/cases/routes/web.php | Case updates go through ApesCic CaseUpdateController for Shelter too. #289. |
| GET / HEAD | /apes-cic/recruitment | apes-cic.recruitment.index | ApesCic\RecruitmentRoleController@index | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| POST | /apes-cic/recruitment | apes-cic.recruitment.store | ApesCic\RecruitmentRoleController@store | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| GET / HEAD | /apes-cic/recruitment/applications | apes-cic.recruitment.applications.index | ApesCic\RecruitmentApplicationController@index | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| GET / HEAD | /apes-cic/recruitment/applications/{recruitmentApplication} | apes-cic.recruitment.applications.show | ApesCic\RecruitmentApplicationController@show | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| PUT / PATCH | /apes-cic/recruitment/applications/{recruitmentApplication} | apes-cic.recruitment.applications.update | ApesCic\RecruitmentApplicationController@update | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| GET / HEAD | /apes-cic/recruitment/{recruitmentRole} | apes-cic.recruitment.show | ApesCic\RecruitmentRoleController@show | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| PUT / PATCH | /apes-cic/recruitment/{recruitmentRole} | apes-cic.recruitment.update | ApesCic\RecruitmentRoleController@update | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| POST | /apes-cic/recruitment/{recruitmentRole}/close | apes-cic.recruitment.close | ApesCic\RecruitmentRoleController@close | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| POST | /apes-cic/recruitment/{recruitmentRole}/publish | apes-cic.recruitment.publish | ApesCic\RecruitmentRoleController@publish | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment, service.selected:apes-cic | Plugin:recruitment (module apes-cic) | plugins/recruitment/routes/web.php | Staff manage on apes-cic only. #290. URL stays under /apes-cic/recruitment. |
| GET / HEAD | /apes-cic/tickets | apes-cic.tickets.index | ApesCic\TicketController@index | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,tickets, service.selected:apes-cic | Plugin:tickets (module apes-cic) | plugins/tickets/routes/web.php | Tickets on this module. #289. |
| POST | /apes-cic/tickets | apes-cic.tickets.store | ApesCic\TicketController@store | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,tickets, service.selected:apes-cic | Plugin:tickets (module apes-cic) | plugins/tickets/routes/web.php | Tickets on this module. #289. |
| DELETE | /apes-cic/tickets/{ticket} | apes-cic.tickets.destroy | ApesCic\TicketController@destroy | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,tickets, service.selected:apes-cic | Plugin:tickets (module apes-cic) | plugins/tickets/routes/web.php | Tickets on this module. #289. |
| GET / HEAD | /apes-cic/tickets/{ticket} | apes-cic.tickets.show | ApesCic\TicketController@show | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,tickets, service.selected:apes-cic | Plugin:tickets (module apes-cic) | plugins/tickets/routes/web.php | Tickets on this module. #289. |
| PUT / PATCH | /apes-cic/tickets/{ticket} | apes-cic.tickets.update | ApesCic\TicketController@update | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,tickets, service.selected:apes-cic | Plugin:tickets (module apes-cic) | plugins/tickets/routes/web.php | Tickets on this module. #289. |
| GET / HEAD | /change-log | change-log.index | ChangeLogController | web | Core | routes/core.php | Change log hub. |
| GET / HEAD | /cookies | cookies | Illuminate\Routing\ViewController | web | Core | routes/core.php | Legal pages. |
| GET / HEAD | /dashboard | dashboard | DashboardController | web, auth, authorization.context, directory.current, account.ready | Core | routes/core.php | Account profile or home dashboard. |
| POST | /email/verification-notification | verification.send | Closure | web, auth, authorization.context, directory.current, throttle:6,1 | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /email/verify | verification.notice | Closure | web, auth, authorization.context, directory.current | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /email/verify/{id}/{hash} | verification.verify | Closure | web, auth, authorization.context, directory.current, signed, throttle:6,1 | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /forgot-password | password.request | Auth\PublicPasswordResetController@create | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /forgot-password | password.email | Auth\PublicPasswordResetController@store | web, guest, throttle:public-password-reset | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /healthz | health | HealthController | — | Core | routes/core.php | Public entry or health. |
| GET / HEAD | /help | help | Illuminate\Routing\ViewController | web | Core | routes/core.php | Legal pages. |
| GET / HEAD | /login | public.login | Auth\PublicAuthController@showLogin | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /login | public.login.submit | Auth\PublicAuthController@login | web, guest, throttle:public-login | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /onboarding | onboarding.edit | OnboardingController@edit | web, auth, authorization.context, directory.current | Core | routes/core.php | Account profile or home dashboard. |
| PUT | /onboarding | onboarding.update | OnboardingController@update | web, auth, authorization.context, directory.current | Core | routes/core.php | Account profile or home dashboard. |
| GET / HEAD | /petcare | petcare.index | SubCoreController@show | web, auth, authorization.context, directory.current, account.ready, service.selected:pet-care-clinic | Module:pet-care-clinic | modules/pet-care-clinic/routes/web.php | Module hub. Prefix stays /petcare even though the slug is pet-care-clinic. #284–#286. |
| GET / HEAD | /petcare/consultations | petcare.consultations.index | PetCare\ConsultationController@index | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,consultations, service.selected:pet-care-clinic | Plugin:consultations (module pet-care-clinic) | plugins/consultations/routes/web.php | Depends on pet profiles. #290. |
| POST | /petcare/consultations | petcare.consultations.store | PetCare\ConsultationController@store | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,consultations, service.selected:pet-care-clinic | Plugin:consultations (module pet-care-clinic) | plugins/consultations/routes/web.php | Depends on pet profiles. #290. |
| GET / HEAD | /petcare/consultations/{consultation} | petcare.consultations.show | PetCare\ConsultationController@show | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,consultations, service.selected:pet-care-clinic | Plugin:consultations (module pet-care-clinic) | plugins/consultations/routes/web.php | Depends on pet profiles. #290. |
| PUT / PATCH | /petcare/consultations/{consultation} | petcare.consultations.update | PetCare\ConsultationController@update | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,consultations, service.selected:pet-care-clinic | Plugin:consultations (module pet-care-clinic) | plugins/consultations/routes/web.php | Depends on pet profiles. #290. |
| GET / HEAD | /petcare/pet-profiles | petcare.pet-profiles | Closure | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Legacy redirect to the pets index. Keep. #293. |
| GET / HEAD | /petcare/pets | petcare.pets.index | PetCare\PetProfileController@index | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| POST | /petcare/pets | petcare.pets.store | PetCare\PetProfileController@store | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /petcare/pets/{pet} | petcare.pets.show | PetCare\PetProfileController@show | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| PUT / PATCH | /petcare/pets/{pet} | petcare.pets.update | PetCare\PetProfileController@update | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /petcare/pets/{pet}/photo | petcare.pets.photo | PetCare\PetProfileController@photo | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,pet-profiles, service.selected:pet-care-clinic | Plugin:pet-profiles (module pet-care-clinic) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /petcare/tickets | petcare.tickets.index | ApesCic\TicketController@index | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,tickets, service.selected:pet-care-clinic | Plugin:tickets (module pet-care-clinic) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| POST | /petcare/tickets | petcare.tickets.store | ApesCic\TicketController@store | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,tickets, service.selected:pet-care-clinic | Plugin:tickets (module pet-care-clinic) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| GET / HEAD | /petcare/tickets/{ticket} | petcare.tickets.show | ApesCic\TicketController@show | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,tickets, service.selected:pet-care-clinic | Plugin:tickets (module pet-care-clinic) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| PUT / PATCH | /petcare/tickets/{ticket} | petcare.tickets.update | ApesCic\TicketController@update | web, auth, authorization.context, directory.current, account.ready, module.available:pet-care-clinic,tickets, service.selected:pet-care-clinic | Plugin:tickets (module pet-care-clinic) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| GET / HEAD | /privacy | privacy | Illuminate\Routing\ViewController | web | Core | routes/core.php | Legal pages. |
| GET / HEAD | /profile | profile.edit | ProfileController@edit | web, auth, authorization.context, directory.current, account.ready | Core | routes/core.php | Account profile or home dashboard. |
| PUT | /profile | profile.update | ProfileController@update | web, auth, authorization.context, directory.current, account.ready | Core | routes/core.php | Account profile or home dashboard. |
| PUT | /profile/password | profile.password.update | ProfileController@updatePassword | web, auth, authorization.context, directory.current, account.ready, throttle:public-password-change | Core | routes/core.php | Account profile or home dashboard. |
| GET / HEAD | /profile/staff-photo | profile.staff-photo | ProfileController@staffPhoto | web, auth, authorization.context, directory.current, account.ready | Core | routes/core.php | Account profile or home dashboard. |
| POST | /qa/switch-role | qa.switch-role | Auth\PublicAuthController@qaSwitchRole | web | Core | routes/core-auth.php | Local QA role switch. Core. Not a production module route. |
| GET / HEAD | /recruitment | recruitment.index | RecruitmentBoardController@index | web, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| GET / HEAD | /recruitment/applications | recruitment.applications.index | PublicRecruitmentApplicationController@index | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| GET / HEAD | /recruitment/applications/{recruitmentApplication} | recruitment.applications.show | PublicRecruitmentApplicationController@show | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| POST | /recruitment/applications/{recruitmentApplication}/withdraw | recruitment.applications.withdraw | PublicRecruitmentApplicationController@withdraw | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| GET / HEAD | /recruitment/{recruitmentRole} | recruitment.show | RecruitmentBoardController@show | web, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| POST | /recruitment/{recruitmentRole}/apply | recruitment.apply | PublicRecruitmentApplicationController@store | web, auth, authorization.context, directory.current, account.ready, module.available:apes-cic,recruitment | Plugin:recruitment | plugins/recruitment/routes/public.php | Public board. Enabling module is apes-cic. No module prefix. #290. URL stays. |
| GET / HEAD | /register | public.register | Auth\PublicAuthController@showRegister | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /register | public.register.submit | Auth\PublicAuthController@register | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /reset-password | password.update | Auth\PublicPasswordResetController@update | web, guest, throttle:public-password-reset | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /reset-password/{token} | password.reset | Auth\PublicPasswordResetController@edit | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /shelter | shelter.index | SubCoreController@show | web, auth, authorization.context, directory.current, account.ready, service.selected:shelter-rescue | Module:shelter-rescue | modules/shelter-rescue/routes/web.php | Module hub. Prefix stays /shelter even though the slug is shelter-rescue. #284–#286. |
| GET / HEAD | /shelter/cases | shelter.cases.index | Shelter\CaseController@index | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,cases, service.selected:shelter-rescue | Plugin:cases (module shelter-rescue) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| POST | /shelter/cases | shelter.cases.store | Shelter\CaseController@store | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,cases, service.selected:shelter-rescue | Plugin:cases (module shelter-rescue) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| GET / HEAD | /shelter/cases/{case} | shelter.cases.show | Shelter\CaseController@show | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,cases, service.selected:shelter-rescue | Plugin:cases (module shelter-rescue) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| PUT / PATCH | /shelter/cases/{case} | shelter.cases.update | Shelter\CaseController@update | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,cases, service.selected:shelter-rescue | Plugin:cases (module shelter-rescue) | plugins/cases/routes/web.php | Cases. ShelterCase row is scoped by module slug. #289. |
| POST | /shelter/cases/{case}/updates | shelter.cases.updates.store | ApesCic\CaseUpdateController@store | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,cases, service.selected:shelter-rescue | Plugin:cases (module shelter-rescue) | plugins/cases/routes/web.php | Case updates go through ApesCic CaseUpdateController for Shelter too. #289. |
| GET / HEAD | /shelter/pet-profiles | shelter.pet-profiles | Closure | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Legacy redirect to the pets index. Keep. #293. |
| GET / HEAD | /shelter/pets | shelter.pets.index | Shelter\PetProfileController@index | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| POST | /shelter/pets | shelter.pets.store | Shelter\PetProfileController@store | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /shelter/pets/{pet} | shelter.pets.show | Shelter\PetProfileController@show | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| PUT / PATCH | /shelter/pets/{pet} | shelter.pets.update | Shelter\PetProfileController@update | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /shelter/pets/{pet}/photo | shelter.pets.photo | Shelter\PetProfileController@photo | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,pet-profiles, service.selected:shelter-rescue | Plugin:pet-profiles (module shelter-rescue) | plugins/pet-profiles/routes/web.php | Pet profiles live at /pets, not /pet-profiles. #291. |
| GET / HEAD | /shelter/tickets | shelter.tickets.index | ApesCic\TicketController@index | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,tickets, service.selected:shelter-rescue | Plugin:tickets (module shelter-rescue) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| POST | /shelter/tickets | shelter.tickets.store | ApesCic\TicketController@store | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,tickets, service.selected:shelter-rescue | Plugin:tickets (module shelter-rescue) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| GET / HEAD | /shelter/tickets/{ticket} | shelter.tickets.show | ApesCic\TicketController@show | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,tickets, service.selected:shelter-rescue | Plugin:tickets (module shelter-rescue) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| PUT / PATCH | /shelter/tickets/{ticket} | shelter.tickets.update | ApesCic\TicketController@update | web, auth, authorization.context, directory.current, account.ready, module.available:shelter-rescue,tickets, service.selected:shelter-rescue | Plugin:tickets (module shelter-rescue) | plugins/tickets/routes/web.php | Same ApesCic TicketController as APES CIC. #289. |
| GET / HEAD | /staff/auth/callback | staff.auth.callback | Auth\OidcAuthController@callback | web | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /staff/auth/login | staff.auth.login | Auth\OidcAuthController@login | web | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /staff/auth/logout | auth.logout | Auth\OidcAuthController@logout | web, auth | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /staff/login | staff.login | Closure | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| POST | /staff/login | staff.local-login.submit | Auth\PublicAuthController@localStaffLogin | web, guest | Core | routes/core-auth.php | Auth. Staff OIDC is not the public password reset. |
| GET / HEAD | /storage/pet-profiles/{path?} | — | Closure | web | Plugin:pet-profiles | plugins/pet-profiles/routes/web.php | Deliberate 404 so public disk paths are not browsable. #291. |
| GET / HEAD | /superadmin | superadmin.index | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:superadmin.access | Core | routes/core-legacy.php | Legacy redirect into Admin (#252). Keep. 301 when #293 converts stable redirects. |
| GET / HEAD | /superadmin/groups | superadmin.groups | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.groups.view | Core | routes/core-legacy.php | Legacy redirect into Admin (#252). Keep. 301 when #293 converts stable redirects. |
| GET / HEAD | /superadmin/modules | superadmin.modules | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.view | Core | routes/core-legacy.php | Legacy redirect into Admin (#252). Keep. 301 when #293 converts stable redirects. |
| GET / HEAD | /superadmin/plugins | superadmin.plugins | Closure | web, auth, authorization.context, directory.current, account.ready, admin.denial-audit, can:admin.modules.view | Core | routes/core-legacy.php | Legacy redirect into Admin (#252). Keep. 301 when #293 converts stable redirects. |
| GET / HEAD | /support/attachments/{attachment} | support.attachments.download | SupportAttachmentController@download | web, auth, authorization.context, directory.current, account.ready | Core | routes/core.php | Shared attachments. |
| GET / HEAD | /terms | terms | Illuminate\Routing\ViewController | web | Core | routes/core.php | Legal pages. |

## Migrations

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| database/migrations/0001_01_01_000000_create_users_table.php | Core | database/migrations (Core) | users. |
| database/migrations/0001_01_01_000001_create_cache_table.php | Core | database/migrations (Core) | Framework cache. |
| database/migrations/0001_01_01_000002_create_jobs_table.php | Core | database/migrations (Core) | Framework queues. |
| database/migrations/2026_07_24_101408_create_support_tickets_table.php | Plugin:tickets | plugins/tickets/database/migrations | support_tickets. #289. Do not rename the table in the move. |
| database/migrations/2026_07_24_101409_create_support_ticket_messages_table.php | Plugin:tickets | plugins/tickets/database/migrations | support_ticket_messages. #289. |
| database/migrations/2026_07_24_101410_create_pet_profiles_table.php | Plugin:pet-profiles | plugins/pet-profiles/database/migrations | pet_profiles. Shared. #291. |
| database/migrations/2026_07_24_101411_create_shelter_cases_table.php | Plugin:cases | plugins/cases/database/migrations | shelter_cases, including case_type. Also used for APES CIC. Keep the table name. #289. |
| database/migrations/2026_07_24_101412_create_pet_care_consultations_table.php | Plugin:consultations | plugins/consultations/database/migrations | #290. |
| database/migrations/2026_07_24_101413_create_user_profiles_table.php | Core | database/migrations (Core) | Profiles. |
| database/migrations/2026_07_24_103415_create_notifications_table.php | Core | database/migrations (Core) | notifiable_type stores the User FQCN. #281. |
| database/migrations/2026_07_24_105009_create_audit_logs_table.php | Core | database/migrations (Core) | auditable_type is a nullable FQCN. #281. |
| database/migrations/2026_07_27_130000_add_access_compatibility_to_users_table.php | Core | database/migrations (Core) | identity_type. Not a polymorphic model type. |
| database/migrations/2026_07_28_000000_create_permission_tables.php | Core | database/migrations (Core) | Spatie tables. model_type stores User FQCN. #281. Permission names are not rewritten here. |
| database/migrations/2026_07_28_000100_cut_over_authorization_domain.php | Core | database/migrations (Core) | Access cutover. Rewrites model_type for User. |
| database/migrations/2026_08_06_000000_create_module_installations_table.php | Core | database/migrations (Core) | module_installations (sub_core_key, module_key). Plugin enablement store. Keep columns until a child renames them. |
| database/migrations/2026_08_08_000000_add_account_lifecycle_foundation.php | Core | database/migrations (Core) | Account lifecycle. |
| database/migrations/2026_08_10_000000_create_maintenance_windows_table.php | Core | database/migrations (Core) | Maintenance. |
| database/migrations/2026_08_10_010000_add_apes_cic_ticket_case_foundation.php | Plugin:tickets + Plugin:cases | Split only if a child extracts it; otherwise leave the historical file in database/migrations | Mixed tickets and cases, including APES CIC categories. Do not rewrite published migrations. #289 notes the coupling. |
| database/migrations/2026_08_19_000000_create_staff_profiles_and_split_identities.php | Core | database/migrations (Core) | Staff profiles. Touches model_has_roles.model_type. |
| database/migrations/2026_08_20_120000_add_app_enabled_to_directory_groups_table.php | Core | database/migrations (Core) | Directory groups. |
| database/migrations/2026_08_22_120000_create_module_settings_table.php | Core | database/migrations (Core) | module_settings. Plugin settings store. #288. |
| database/migrations/2026_08_22_120100_extend_tickets_cases_and_create_support_attachments.php | Core + Plugin:tickets + Plugin:cases | Historical file stays in database/migrations | Creates support_attachments (Core, attachable_type) and extends ticket/case columns. #281/#289. |
| database/migrations/2026_08_24_000001_add_directory_sync_user_counts.php | Core | database/migrations (Core) | Directory sync. |
| database/migrations/2026_08_24_000002_migrate_myapes_to_myapesaccount_groups.php | Core | database/migrations (Core) | Directory group rename data move. |
| database/migrations/2026_08_24_000003_extend_directory_authorization_roles.php | Core | database/migrations (Core) | Access roles. |
| database/migrations/2026_09_12_180000_add_registration_consented_at_to_users_table.php | Core | database/migrations (Core) | Registration consent. |
| database/migrations/2026_09_24_165916_create_recruitment_roles_table.php | Plugin:recruitment | plugins/recruitment/database/migrations | recruitment_roles. #290. Leave the historical file where it is until the plugin move copies forward-only policy. |
| database/migrations/2026_09_24_174049_create_recruitment_applications_table.php | Plugin:recruitment | plugins/recruitment/database/migrations | recruitment_applications. #290. |

## Views

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| resources/views/admin/_navigation.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/groups.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/job-roles/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/job-roles/show.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/layout.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/partials/pack-script.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/access/permissions.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/groups/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/groups/show.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/maintenance/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/modules/index.blade.php | Core | resources/views/admin/plugins | Plugins index and settings. Path still says modules. #288. URL stays. |
| resources/views/admin/modules/settings-recruitment.blade.php | Core | resources/views/admin/plugins | Plugins index and settings. Path still says modules. #288. URL stays. |
| resources/views/admin/modules/settings.blade.php | Core | resources/views/admin/plugins | Plugins index and settings. Path still says modules. #288. URL stays. |
| resources/views/admin/permissions/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/roles/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/roles/show.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/users/index.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/admin/users/show.blade.php | Core | resources/views/admin | Admin shell. |
| resources/views/apes-cic/cases/index.blade.php | Plugin:cases | plugins/cases/resources/views/apes-cic | APES CIC case screens. #289. |
| resources/views/apes-cic/cases/show.blade.php | Plugin:cases | plugins/cases/resources/views/apes-cic | APES CIC case screens. #289. |
| resources/views/apes-cic/recruitment/_navigation.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/staff/_navigation.blade.php | v0.36.1 Roles / Applications submenu. URLs unchanged. #290. |
| resources/views/apes-cic/recruitment/applications/index.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/staff | Staff manage. Includes the v0.36.1 submenu. #290. |
| resources/views/apes-cic/recruitment/applications/show.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/staff | Staff manage. #290. |
| resources/views/apes-cic/recruitment/index.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/staff | Staff manage. #290. |
| resources/views/apes-cic/recruitment/show.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/staff | Staff manage. #290. |
| resources/views/apes-cic/tickets/index.blade.php | Plugin:tickets | plugins/tickets/resources/views | Only ticket views in the app. Shelter and Pet Care render these too. #289. |
| resources/views/apes-cic/tickets/show.blade.php | Plugin:tickets | plugins/tickets/resources/views | Only ticket views in the app. Shelter and Pet Care render these too. #289. |
| resources/views/auth/forgot-password.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/landing.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/login.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/public-login.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/public-register.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/reset-password.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/staff-login.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/auth/verify-email.blade.php | Core | resources/views/auth | Auth screens. |
| resources/views/change-log/index.blade.php | Core | resources/views/change-log | Change log. |
| resources/views/components/attention-item-link.blade.php | Core | resources/views/components | Shared components. attention-item-link is fed by plugin dashboard providers. |
| resources/views/components/directory-group-list.blade.php | Core | resources/views/components | Shared components. attention-item-link is fed by plugin dashboard providers. |
| resources/views/components/mascot-tip.blade.php | Core | resources/views/components | Shared components. attention-item-link is fed by plugin dashboard providers. |
| resources/views/dashboard.blade.php | Core | resources/views/dashboard.blade.php | Dashboard shell. Cards come from plugin providers. |
| resources/views/errors/403.blade.php | Core | resources/views/errors | Branded errors and maintenance. |
| resources/views/errors/404.blade.php | Core | resources/views/errors | Branded errors and maintenance. |
| resources/views/errors/maintenance.blade.php | Core | resources/views/errors | Branded errors and maintenance. |
| resources/views/layouts/app.blade.php | Core | resources/views/layouts | Chrome. Knows recruitment nav and admin permissions. Stays Core; plugins contribute nav via the registry. |
| resources/views/legal/_nav.blade.php | Core | resources/views/legal | Privacy, cookies, terms, help. |
| resources/views/legal/cookies.blade.php | Core | resources/views/legal | Privacy, cookies, terms, help. |
| resources/views/legal/help.blade.php | Core | resources/views/legal | Privacy, cookies, terms, help. |
| resources/views/legal/privacy.blade.php | Core | resources/views/legal | Privacy, cookies, terms, help. |
| resources/views/legal/terms.blade.php | Core | resources/views/legal | Privacy, cookies, terms, help. |
| resources/views/partials/_github-links.blade.php | Core | resources/views/partials/_github-links.blade.php | Footer links. |
| resources/views/partials/category-cascade-script.blade.php | Plugin:tickets | plugins/tickets/resources/views/partials/category-cascade-script.blade.php | Ticket/case category cascade. Also used by cases. Duplicate into cases or keep a shared partial in Core if both plugins need it. #289. |
| resources/views/partials/pet-profile-select.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/partials/pet-profile-select.blade.php | Shared pet picker. #291. |
| resources/views/partials/staff-empty-pet-select.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/partials/staff-empty-pet-select.blade.php | #291. |
| resources/views/petcare/consultations/index.blade.php | Plugin:consultations | plugins/consultations/resources/views | #290. |
| resources/views/petcare/consultations/show.blade.php | Plugin:consultations | plugins/consultations/resources/views | #290. |
| resources/views/petcare/pets/index.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/pet-care-clinic | #291. |
| resources/views/petcare/pets/show.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/pet-care-clinic | #291. |
| resources/views/profile/_account-fields.blade.php | Core | resources/views/profile | Profile and onboarding. |
| resources/views/profile/edit.blade.php | Core | resources/views/profile | Profile and onboarding. |
| resources/views/profile/onboarding.blade.php | Core | resources/views/profile | Profile and onboarding. |
| resources/views/profile/staff-edit.blade.php | Core | resources/views/profile | Profile and onboarding. |
| resources/views/recruitment/_navigation.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/public | Public board and my applications. #290. |
| resources/views/recruitment/applications/index.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/public | Public board and my applications. #290. |
| resources/views/recruitment/applications/show.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/public | Public board and my applications. #290. |
| resources/views/recruitment/index.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/public | Public board and my applications. #290. |
| resources/views/recruitment/show.blade.php | Plugin:recruitment | plugins/recruitment/resources/views/public | Public board and my applications. #290. |
| resources/views/shelter/cases/index.blade.php | Plugin:cases | plugins/cases/resources/views/shelter-rescue | Shelter case screens. #289. |
| resources/views/shelter/cases/show.blade.php | Plugin:cases | plugins/cases/resources/views/shelter-rescue | Shelter case screens. #289. |
| resources/views/shelter/pets/index.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/shelter-rescue | #291. |
| resources/views/shelter/pets/show.blade.php | Plugin:pet-profiles | plugins/pet-profiles/resources/views/shelter-rescue | #291. |
| resources/views/sub-cores/show.blade.php | Core | resources/views/modules (hub) | Shared hub for all three modules. #284–#286 give each module a hub view. Old folder name. |
| resources/views/welcome.blade.php | Core | resources/views/welcome.blade.php | Unused Laravel welcome, or landing leftover. Confirm before any move. Not a module. |

## Permissions

Core names are the catalogue in `app/Support/PermissionDescriptions.php`. Plugin names are `FirstPartyModuleRegistry::permissions()` (shipped instances only). Job-role packs in `JobRoleCapabilityPacks`: `admin-overview` grants `admin.analytics.view`; `view-accounts` grants `admin.users.view`; `manage-accounts` grants `admin.users.manage`; `staff-module-work` grants every shipped plugin permission whose ability is not `delete` and that requires a directory context; `module-delete` grants the shipped `delete` abilities. `DefaultJobRoles` seeds Board, Management, Client Services Advisor, and Receptionist with core account permissions only (`admin.analytics.view` and/or `admin.users.view`). It does not seed plugin permissions. Packs are applied when an admin selects them.

| Permission | Layer | Target name | Notes |
| --- | --- | --- | --- |
| staff.access | Core | staff.access (unchanged) | Open the staff workspace. Not an admin.* name. Keep. |
| admin.access | Core | admin.access (unchanged) | Admin shell. Keep. Do not rename to core.*. |
| superadmin.access | Core | superadmin.access (unchanged) | Technical admin. Keep as a permission. Nav label is Admin. |
| admin.users.view | Core | admin.users.view (unchanged) | Pack view-accounts. Default roles: Management, Client Services Advisor, Receptionist. |
| admin.users.manage | Core | admin.users.manage (unchanged) | Pack manage-accounts. |
| admin.analytics.view | Core | admin.analytics.view (unchanged) | Pack admin-overview. Default roles: Board, Management. |
| admin.groups.view | Core | admin.groups.view (unchanged) | Directory groups. No default job-role seed. |
| admin.group-mappings.manage | Core | admin.group-mappings.manage (unchanged) | Group mappings. |
| admin.roles.view | Core | admin.roles.view (unchanged) | Access job roles. Not RecruitmentRole. |
| admin.roles.manage | Core | admin.roles.manage (unchanged) | Custom job roles. |
| admin.permissions.view | Core | admin.permissions.view (unchanged) | Permission catalogue. |
| admin.modules.view | Core | admin.modules.view (unchanged) | View plugin enablement. The name says modules. Keep the string (#292). |
| admin.modules.manage | Core | admin.modules.manage (unchanged) | Manage plugin enablement. Keep the string. |
| admin.maintenance.manage | Core | admin.maintenance.manage (unchanged) | Maintenance. |
| apes-cic.cases.assign | Plugin:cases (module apes-cic) | apes-cic.cases.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.close | Plugin:cases (module apes-cic) | apes-cic.cases.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.comment-own | Plugin:cases (module apes-cic) | apes-cic.cases.comment-own (unchanged) | Ability comment-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.create | Plugin:cases (module apes-cic) | apes-cic.cases.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.delete | Plugin:cases (module apes-cic) | apes-cic.cases.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.update-all | Plugin:cases (module apes-cic) | apes-cic.cases.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.update-own | Plugin:cases (module apes-cic) | apes-cic.cases.update-own (unchanged) | Ability update-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.view-all | Plugin:cases (module apes-cic) | apes-cic.cases.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.cases.view-own | Plugin:cases (module apes-cic) | apes-cic.cases.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.create | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.delete | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.review-applications | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.review-applications (unchanged) | Ability review-applications. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.update | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.update (unchanged) | Ability update. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.view-all | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.recruitment.view-own | Plugin:recruitment (module apes-cic) | apes-cic.recruitment.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.assign | Plugin:tickets (module apes-cic) | apes-cic.tickets.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.close | Plugin:tickets (module apes-cic) | apes-cic.tickets.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.comment-own | Plugin:tickets (module apes-cic) | apes-cic.tickets.comment-own (unchanged) | Ability comment-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.create | Plugin:tickets (module apes-cic) | apes-cic.tickets.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.delete | Plugin:tickets (module apes-cic) | apes-cic.tickets.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.update-all | Plugin:tickets (module apes-cic) | apes-cic.tickets.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.view-all | Plugin:tickets (module apes-cic) | apes-cic.tickets.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| apes-cic.tickets.view-own | Plugin:tickets (module apes-cic) | apes-cic.tickets.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.assign | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.close | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.create | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.update-all | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.update-own | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.update-own (unchanged) | Ability update-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.view-all | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.consultations.view-own | Plugin:consultations (module pet-care-clinic) | pet-care-clinic.consultations.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.pet-profiles.create | Plugin:pet-profiles (module pet-care-clinic) | pet-care-clinic.pet-profiles.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.pet-profiles.update-all | Plugin:pet-profiles (module pet-care-clinic) | pet-care-clinic.pet-profiles.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.pet-profiles.update-own | Plugin:pet-profiles (module pet-care-clinic) | pet-care-clinic.pet-profiles.update-own (unchanged) | Ability update-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.pet-profiles.view-all | Plugin:pet-profiles (module pet-care-clinic) | pet-care-clinic.pet-profiles.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.pet-profiles.view-own | Plugin:pet-profiles (module pet-care-clinic) | pet-care-clinic.pet-profiles.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.assign | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.close | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.comment-own | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.comment-own (unchanged) | Ability comment-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.create | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.delete | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.update-all | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.view-all | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| pet-care-clinic.tickets.view-own | Plugin:tickets (module pet-care-clinic) | pet-care-clinic.tickets.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.assign | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.close | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.comment-own | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.comment-own (unchanged) | Ability comment-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.create | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.delete | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.update-all | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.update-own | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.update-own (unchanged) | Ability update-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.view-all | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.cases.view-own | Plugin:cases (module shelter-rescue) | shelter-rescue.cases.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.pet-profiles.create | Plugin:pet-profiles (module shelter-rescue) | shelter-rescue.pet-profiles.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.pet-profiles.update-all | Plugin:pet-profiles (module shelter-rescue) | shelter-rescue.pet-profiles.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.pet-profiles.update-own | Plugin:pet-profiles (module shelter-rescue) | shelter-rescue.pet-profiles.update-own (unchanged) | Ability update-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.pet-profiles.view-all | Plugin:pet-profiles (module shelter-rescue) | shelter-rescue.pet-profiles.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.pet-profiles.view-own | Plugin:pet-profiles (module shelter-rescue) | shelter-rescue.pet-profiles.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.assign | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.assign (unchanged) | Ability assign. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.close | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.close (unchanged) | Ability close. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.comment-own | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.comment-own (unchanged) | Ability comment-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.create | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.create (unchanged) | Ability create. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.delete | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.delete (unchanged) | Ability delete. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.update-all | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.update-all (unchanged) | Ability update-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.view-all | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.view-all (unchanged) | Ability view-all. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |
| shelter-rescue.tickets.view-own | Plugin:tickets (module shelter-rescue) | shelter-rescue.tickets.view-own (unchanged) | Ability view-own. Already {module}.{plugin}.{ability} once vocabulary flips. Granted via staff-module-work or module-delete when the ability requires a directory context. |

## Config

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| config/app.php | Core | config/app.php | Framework config. |
| config/auth.php | Core | config/auth.php | Framework config. |
| config/cache.php | Core | config/cache.php | Framework config. |
| config/database.php | Core | config/database.php | Framework config. |
| config/filesystems.php | Core | config/filesystems.php | Framework config. |
| config/logging.php | Core | config/logging.php | Framework config. |
| config/mail.php | Core | config/mail.php | Framework config. |
| config/maintenance.php | Core | config/maintenance.php | Maintenance mode. |
| config/modules.php | Core | config/modules.php | Lock wait and projection cache for plugin enablement. Name stays until #283. |
| config/myapes.php | Core | config/myapes.php | App identity, audit retention, auth options. |
| config/permission.php | Core | config/permission.php | Spatie. model_type morphs. #281. |
| config/queue.php | Core | config/queue.php | Framework config. |
| config/services.php | Core | config/services.php | Framework config. |
| config/session.php | Core | config/session.php | Framework config. |
| resources/data/module-runtime-contract.json | Core | resources/data/module-runtime-contract.json | sub_cores, module_types, shipped_instances, application_version. Vocabulary change is docs-only until #283. Version bumps still edit application_version. |

## Tests

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| tests/Fakes/FakeDirectoryUserSynchronizer.php | Core | tests/Fakes/FakeDirectoryUserSynchronizer.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Fakes/FakeLdapGroupResolver.php | Core | tests/Fakes/FakeLdapGroupResolver.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Fakes/FakeLdapUserResolver.php | Core | tests/Fakes/FakeLdapUserResolver.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Fakes/FakeMaintenanceModeGateway.php | Core | tests/Fakes/FakeMaintenanceModeGateway.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Fakes/FakeOidcIdentityProvider.php | Core | tests/Fakes/FakeOidcIdentityProvider.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Feature/AccessCompatibilityCommandTest.php | Core | tests/Feature/AccessCompatibilityCommandTest.php | Access compatibility. |
| tests/Feature/AccessCompatibilityMigrationTest.php | Core | tests/Feature/AccessCompatibilityMigrationTest.php | Access compatibility. |
| tests/Feature/AccountLifecycleReadinessTest.php | Core | tests/Feature/AccountLifecycleReadinessTest.php | Accounts. |
| tests/Feature/AccountLifecycleSchemaTest.php | Core | tests/Feature/AccountLifecycleSchemaTest.php | Accounts. |
| tests/Feature/AdminAccessAndViewsTest.php | Core | tests/Feature/AdminAccessAndViewsTest.php | Admin shell or access. |
| tests/Feature/AdminAccessWorkspaceTest.php | Core | tests/Feature/AdminAccessWorkspaceTest.php | Admin shell or access. |
| tests/Feature/AdminAnalyticsDashboardTest.php | Core | tests/Feature/AdminAnalyticsDashboardTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/AdminDirectoryManagementTest.php | Core | tests/Feature/AdminDirectoryManagementTest.php | Admin shell or access. |
| tests/Feature/AdminGroupMembershipTest.php | Core | tests/Feature/AdminGroupMembershipTest.php | Admin shell or access. |
| tests/Feature/AdminLocalPublicPasswordResetTest.php | Core | tests/Feature/AdminLocalPublicPasswordResetTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AdminMaintenanceManagementTest.php | Core | tests/Feature/AdminMaintenanceManagementTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/AdminPendingFirstLoginChaseTest.php | Core | tests/Feature/AdminPendingFirstLoginChaseTest.php | Admin shell or access. |
| tests/Feature/AdminProfileManagementTest.php | Core | tests/Feature/AdminProfileManagementTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/AdminRoleAndUserManagementTest.php | Core | tests/Feature/AdminRoleAndUserManagementTest.php | Admin shell or access. |
| tests/Feature/AdminShellNavigationTest.php | Core | tests/Feature/AdminShellNavigationTest.php | Admin shell or access. |
| tests/Feature/AdminUserDetailUxTest.php | Core | tests/Feature/AdminUserDetailUxTest.php | Admin shell or access. |
| tests/Feature/ApesCicCasesWorkflowTest.php | Plugin:cases | plugins/cases/tests/ApesCicCasesWorkflowTest.php | #289. |
| tests/Feature/ApesCicFoundationMigrationTest.php | Plugin:tickets + Plugin:cases | tests/Feature/ApesCicFoundationMigrationTest.php | Mixed foundation migration. Stays beside the historical migration. #289. |
| tests/Feature/ApesCicModuleFoundationTest.php | Core | tests/Feature/ApesCicModuleFoundationTest.php | Registry foundation for the APES CIC module. #284. |
| tests/Feature/ApesCicTicketCaseEnhancementTest.php | Plugin:tickets + Plugin:cases | tests/Feature/ApesCicTicketCaseEnhancementTest.php | Mixed ticket and case enhancement. #289. |
| tests/Feature/Auth/CloudronOidcRedirectTest.php | Core | tests/Feature/CloudronOidcRedirectTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/DirectoryRevalidationTest.php | Core | tests/Feature/DirectoryRevalidationTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/ForceReauthenticationCookieTest.php | Core | tests/Feature/ForceReauthenticationCookieTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/GuestAuthenticationRedirectTest.php | Core | tests/Feature/GuestAuthenticationRedirectTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/HomeLandingRedirectTest.php | Core | tests/Feature/HomeLandingRedirectTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/OidcAccountLinkingTest.php | Core | tests/Feature/OidcAccountLinkingTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/PublicLocalForgotPasswordTest.php | Core | tests/Feature/PublicLocalForgotPasswordTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/Auth/StaffOidcAuthenticationTest.php | Core | tests/Feature/StaffOidcAuthenticationTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthReadinessCommandTest.php | Core | tests/Feature/AuthReadinessCommandTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationAccessMatrixTest.php | Core | tests/Feature/AuthorizationAccessMatrixTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationAuthenticationTest.php | Core | tests/Feature/AuthorizationAuthenticationTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationCutoverConcurrencyTest.php | Core | tests/Feature/AuthorizationCutoverConcurrencyTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationCutoverGuardTest.php | Core | tests/Feature/AuthorizationCutoverGuardTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationDeletionBoundaryTest.php | Core | tests/Feature/AuthorizationDeletionBoundaryTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationDirectPermissionTest.php | Core | tests/Feature/AuthorizationDirectPermissionTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationGateTest.php | Core | tests/Feature/AuthorizationGateTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationLifecycleCommandTest.php | Core | tests/Feature/AuthorizationLifecycleCommandTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationLockOrderTest.php | Core | tests/Feature/AuthorizationLockOrderTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationMutationServiceTest.php | Core | tests/Feature/AuthorizationMutationServiceTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationPolicyTest.php | Core | tests/Feature/AuthorizationPolicyTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationQueryScopeTest.php | Core | tests/Feature/AuthorizationQueryScopeTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationRoleMaterializerTest.php | Core | tests/Feature/AuthorizationRoleMaterializerTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationRuntimeSourceContractTest.php | Core | tests/Feature/AuthorizationRuntimeSourceContractTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/AuthorizationSchemaTest.php | Core | tests/Feature/AuthorizationSchemaTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/BrandedErrorPagesTest.php | Core | tests/Feature/BrandedErrorPagesTest.php | Error pages. |
| tests/Feature/ChangeLogPageTest.php | Core | tests/Feature/ChangeLogPageTest.php | Change log. Version-pinned. |
| tests/Feature/DashboardAttentionTest.php | Core | tests/Feature/DashboardAttentionTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/DirectoryCatalogueSynchronizerTest.php | Core | tests/Feature/DirectoryCatalogueSynchronizerTest.php | Directory sync. |
| tests/Feature/DirectoryGroupMappingServiceTest.php | Core | tests/Feature/DirectoryGroupMappingServiceTest.php | Directory sync. |
| tests/Feature/DirectoryRoleSynchronizerTest.php | Core | tests/Feature/DirectoryRoleSynchronizerTest.php | Directory sync. |
| tests/Feature/DirectorySyncWiringTest.php | Core | tests/Feature/DirectorySyncWiringTest.php | Directory sync. |
| tests/Feature/DirectoryUserSynchronizerTest.php | Core | tests/Feature/DirectoryUserSynchronizerTest.php | Directory sync. |
| tests/Feature/ExampleTest.php | Core | tests/ExampleTest.php | Framework sample. |
| tests/Feature/ForwardOnlyDatabaseMigrationsTest.php | Core | tests/Feature/ForwardOnlyDatabaseMigrationsTest.php | Migrations stay discoverable. #281 must not hide historical files. |
| tests/Feature/FreshInstallAuthorizationSeederTest.php | Core | tests/Feature/FreshInstallAuthorizationSeederTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/HealthAndThemeTest.php | Core | tests/Feature/HealthAndThemeTest.php | Health and theme. Version-pinned. |
| tests/Feature/LocalQaAuthTest.php | Core | tests/Feature/LocalQaAuthTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/MaintenanceModeResponseTest.php | Core | tests/Feature/MaintenanceModeResponseTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/MascotHelperTest.php | Core | tests/Feature/MascotHelperTest.php | Mascot helper. |
| tests/Feature/ModuleAdministrationAndNavigationTest.php | Core | tests/Feature/ModuleAdministrationAndNavigationTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleAttentionProviderTest.php | Core | tests/Feature/ModuleAttentionProviderTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleInstallationSynchronizationTest.php | Core | tests/Feature/ModuleInstallationSynchronizationTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleLifecycleConcurrencyTest.php | Core | tests/Feature/ModuleLifecycleConcurrencyTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleLifecycleTest.php | Core | tests/Feature/ModuleLifecycleTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModulePermissionGateTest.php | Core | tests/Feature/ModulePermissionGateTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModulePermissionMigrationTest.php | Core | tests/Feature/ModulePermissionMigrationTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleProviderContractTest.php | Core | tests/Feature/ModuleProviderContractTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleRegistryTest.php | Core | tests/Feature/ModuleRegistryTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleRollbackCompatibilityTest.php | Core | tests/Feature/ModuleRollbackCompatibilityTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleRuntimeSourceContractTest.php | Core | tests/Feature/ModuleRuntimeSourceContractTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/ModuleStateMiddlewareTest.php | Core | tests/Feature/ModuleStateMiddlewareTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Feature/MyapesToMyapesaccountGroupMigrationTest.php | Core | tests/Feature/MyapesToMyapesaccountGroupMigrationTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/PermissionSchemaMigrationTest.php | Core | tests/Feature/PermissionSchemaMigrationTest.php | Permission tables. |
| tests/Feature/PetCareConsultationWorkflowTest.php | Plugin:consultations | plugins/consultations/tests/PetCareConsultationWorkflowTest.php | #290. |
| tests/Feature/PetCarePetProfileWorkflowTest.php | Core | tests/Feature/PetCarePetProfileWorkflowTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/PetCareTicketWorkflowTest.php | Plugin:tickets | plugins/tickets/tests/PetCareTicketWorkflowTest.php | Module pet-care-clinic through the shared controller. #289. |
| tests/Feature/PluginSettingsRegistryTest.php | Core | tests/Feature/PluginSettingsRegistryTest.php | Plugin settings registry. #288. |
| tests/Feature/PrepareChangeLogCommandTest.php | Core | tests/Feature/PrepareChangeLogCommandTest.php | Change log. Version-pinned. |
| tests/Feature/ProductionUpgradePreflightTest.php | Core | tests/Feature/ProductionUpgradePreflightTest.php | Upgrade preflight. |
| tests/Feature/ProfileAccountEmailTest.php | Core | tests/Feature/ProfileAccountEmailTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/ProfileLocalPasswordChangeTest.php | Core | tests/Feature/ProfileLocalPasswordChangeTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Feature/PublicAccountLifecycleTest.php | Core | tests/Feature/PublicAccountLifecycleTest.php | Accounts. |
| tests/Feature/PublicChromeNamingTest.php | Core | tests/Feature/PublicChromeNamingTest.php | Naming in chrome. Glossary-sensitive. #267. |
| tests/Feature/PublicFrontendSeparationTest.php | Core | tests/Feature/PublicFrontendSeparationTest.php | Public vs staff chrome. |
| tests/Feature/PublicLegalPagesTest.php | Core | tests/Feature/PublicLegalPagesTest.php | Legal pages. |
| tests/Feature/PublicRecruitmentBoardTest.php | Plugin:recruitment | plugins/recruitment/tests/PublicRecruitmentBoardTest.php | Includes public board and staff review. #290. |
| tests/Feature/PublicShelterPetPageTest.php | Plugin:pet-profiles | plugins/pet-profiles/tests/PublicShelterPetPageTest.php | Module shelter-rescue. #291. |
| tests/Feature/PublicShelterPetSaveFlashTest.php | Plugin:pet-profiles | plugins/pet-profiles/tests/PublicShelterPetSaveFlashTest.php | Module shelter-rescue. #291. |
| tests/Feature/PublicTicketActivityTest.php | Plugin:tickets | plugins/tickets/tests/PublicTicketActivityTest.php | #289. |
| tests/Feature/PublicTicketPriorityTest.php | Plugin:tickets | plugins/tickets/tests/PublicTicketPriorityTest.php | #289. |
| tests/Feature/PublicTicketSaveFlashTest.php | Plugin:tickets | plugins/tickets/tests/PublicTicketSaveFlashTest.php | #289. |
| tests/Feature/RecruitmentApplicationManageTest.php | Plugin:recruitment | plugins/recruitment/tests/RecruitmentApplicationManageTest.php | Includes public board and staff review. #290. |
| tests/Feature/RecruitmentApplicationWorkflowTest.php | Plugin:recruitment | plugins/recruitment/tests/RecruitmentApplicationWorkflowTest.php | Includes public board and staff review. #290. |
| tests/Feature/RecruitmentRoleWorkflowTest.php | Plugin:recruitment | plugins/recruitment/tests/RecruitmentRoleWorkflowTest.php | Includes public board and staff review. #290. |
| tests/Feature/ReleaseHistoryCommandTest.php | Core | tests/Feature/ReleaseHistoryCommandTest.php | Release metadata. Version-pinned. |
| tests/Feature/ReviewFeedbackFixesTest.php | Core | tests/Feature/ReviewFeedbackFixesTest.php | Cross-cutting review fixes. Re-check owners when those screens move. |
| tests/Feature/RolelessApplicationCompatibilityTest.php | Core | tests/Feature/RolelessApplicationCompatibilityTest.php | Application without a job role. |
| tests/Feature/SecurityRemediationTest.php | Core | tests/Feature/SecurityRemediationTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/ServiceHubQuickLinksTest.php | Module:apes-cic + Module:shelter-rescue + Module:pet-care-clinic | tests/Feature/ServiceHubQuickLinksTest.php | Hub links across modules, including pet-profiles redirects. #284–#286. |
| tests/Feature/ShelterCaseWorkflowTest.php | Plugin:cases | plugins/cases/tests/ShelterCaseWorkflowTest.php | #289. |
| tests/Feature/ShelterPetProfileWorkflowTest.php | Core | tests/Feature/ShelterPetProfileWorkflowTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/ShelterTicketWorkflowTest.php | Plugin:tickets | plugins/tickets/tests/ShelterTicketWorkflowTest.php | Module shelter-rescue through the shared controller. #289. |
| tests/Feature/StaffEmptyPetSelectTest.php | Plugin:pet-profiles | plugins/pet-profiles/tests/StaffEmptyPetSelectTest.php | #291. |
| tests/Feature/StaffListUxTest.php | Core | tests/Feature/StaffListUxTest.php | Staff list UX. |
| tests/Feature/StaffProfileSchemaTest.php | Core | tests/Feature/StaffProfileSchemaTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/StaffProfileWorkflowTest.php | Core | tests/Feature/StaffProfileWorkflowTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Feature/UserAccessCompatibilityTest.php | Core | tests/Feature/UserAccessCompatibilityTest.php | Access compatibility. |
| tests/Support/AuthorizationCutoverConcurrencyWorker.php | Core | tests/Support/AuthorizationCutoverConcurrencyWorker.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Support/DirectorySyncTimeoutProbeJob.php | Core | tests/Support/DirectorySyncTimeoutProbeJob.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Support/ForwardOnlyDatabaseMigrations.php | Core | tests/Support/ForwardOnlyDatabaseMigrations.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Support/ModuleLifecycleConcurrencyWorker.php | Core | tests/Support/ModuleLifecycleConcurrencyWorker.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Support/directory-sync-timeout-probe.php | Core | tests/Support/directory-sync-timeout-probe.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/TestCase.php | Core | tests/TestCase.php | Test harness. Stays with the suite that uses it. Not a product layer. |
| tests/Unit/AccessCompatibilitySourceContractTest.php | Core | tests/Feature/AccessCompatibilitySourceContractTest.php | Access compatibility. |
| tests/Unit/ChangeLogPresenterTest.php | Core | tests/Feature/ChangeLogPresenterTest.php | Change log. Version-pinned. |
| tests/Unit/DeploymentAuthenticationContractTest.php | Core | tests/Feature/DeploymentAuthenticationContractTest.php | Auth and account security tests. Stay on the Core boundary (#282) for #225 later. |
| tests/Unit/ExampleTest.php | Core | tests/ExampleTest.php | Framework sample. |
| tests/Unit/JobRoleCapabilityPacksTest.php | Core | tests/Unit/JobRoleCapabilityPacksTest.php | Capability packs. #292. |
| tests/Unit/LaravelMaintenanceModeGatewayTest.php | Core | tests/Feature/LaravelMaintenanceModeGatewayTest.php | Core dashboard, maintenance, directory, or profile coverage. |
| tests/Unit/LdapGroupResolverTest.php | Core | tests/Unit/LdapGroupResolverTest.php | Directory. |
| tests/Unit/ModuleInstanceLockTest.php | Core | tests/Feature/ModuleInstanceLockTest.php | Enablement, registry, middleware, or rollback. Core extension tests until #294 architecture suite exists. |
| tests/Unit/ReleaseHistoryValidatorTest.php | Core | tests/Feature/ReleaseHistoryValidatorTest.php | Release metadata. Version-pinned. |
| tests/Unit/SecurityPolicyDocumentationTest.php | Core | tests/Unit/SecurityPolicyDocumentationTest.php | SECURITY.md contract. |
| tests/Unit/StaffPetCreateReturnTest.php | Plugin:pet-profiles | plugins/pet-profiles/tests/StaffPetCreateReturnTest.php | #291. |
| tests/Unit/UkDateTimeTest.php | Core | tests/Unit/UkDateTimeTest.php | Shared dates. |
| tests/Unit/UkPhoneNumberTest.php | Core | tests/Unit/UkPhoneNumberTest.php | Shared phone numbers. |

## Front-end assets

| Current path | Layer | Target | Notes |
| --- | --- | --- | --- |
| resources/css/app.css | Core | resources/css/app.css | Shared bundle. |
| resources/js/admin-analytics.js | Core | resources/js/admin-analytics.js | Admin overview charts. Core. Data is assembled from plugin analytics providers. |
| resources/js/app.js | Core | resources/js/app.js | Shared bundle. |
| resources/js/change-log.js | Core | resources/js/change-log.js | Change log page. |
| resources/js/change-log.test.js | Core | resources/js/change-log.test.js | Change log page. |

## Console routes

`routes/console.php` schedules `audit:prune` (Core audit) and `RunDirectorySync` (Core directory). The closure command `inspire` is the Laravel sample and is Core-only noise.

Artisan commands under `app/Console/Commands` are listed in the jobs and console table. Signatures `myapes:modules-sync`, `myapes:modules-check`, `myapes:modules-preflight`, and `myapes:modules-rollback-check` operate on plugin enablement. Keep the signatures until [#295](https://github.com/APESCIC/MyAPES-Account/issues/295) adds aliases.
