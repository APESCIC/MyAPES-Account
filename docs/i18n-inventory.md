# Hard-coded UI string inventory

Generated for [#266](https://github.com/APESCIC/MyAPES-Account/issues/266). This is a checklist for Language extraction children — **do not** treat it as a mandate to extract everything in one PR.

Regenerate:

```bash
php artisan lang:inventory --write
```

Scanner: `App\Services\Localisation\HardCodedStringScanner` (reusable by [#276](https://github.com/APESCIC/MyAPES-Account/issues/276)).
Allow-list: `config/lang_inventory.php` → `allowlist`.
Machine JSON (gitignored under storage): `storage/app/lang-inventory.json`.

## Section index (extraction children)

| Extraction issue | Inventory section | Anchor |
| --- | --- | --- |
| [#268](https://github.com/APESCIC/MyAPES-Account/issues/268) Public | [Public](#public-268) + [Plugin: Recruitment](#plugin-recruitment) (public views) + [Plugin: Pet Profiles](#plugin-pet-profiles) | `docs/i18n-inventory.md#public-268` |
| [#269](https://github.com/APESCIC/MyAPES-Account/issues/269) APES CIC staff | [APES CIC staff](#apes-cic-staff-269) + [Cases](#plugin-cases) + [Tickets](#plugin-tickets) + [Consultations](#plugin-consultations) + Recruitment staff views | `docs/i18n-inventory.md#apes-cic-staff-269` |
| [#270](https://github.com/APESCIC/MyAPES-Account/issues/270) Admin shell | [Admin shell](#admin-shell-270) | `docs/i18n-inventory.md#admin-shell-270` |
| [#271](https://github.com/APESCIC/MyAPES-Account/issues/271) Auth / mail / flash | [Auth / emails / notifications / flash / validation](#auth-emails-flash-validation-271) | `docs/i18n-inventory.md#auth-emails-flash-validation-271` |

## Totals

| Area | Count |
| --- | ---: |
| [Public](#public-268) | 0 |
| [APES CIC staff](#apes-cic-staff-269) | 12 |
| [Admin shell](#admin-shell-270) | 0 |
| [Auth / emails / notifications / flash / validation](#auth-emails-flash-validation-271) | 0 |
| [Plugin: Cases](#plugin-cases) | 100 |
| [Plugin: Tickets](#plugin-tickets) | 53 |
| [Plugin: Recruitment](#plugin-recruitment) | 74 |
| [Plugin: Consultations](#plugin-consultations) | 32 |
| [Plugin: Pet Profiles](#plugin-pet-profiles) | 0 |
| [Other / unclassified](#other-unclassified) | 0 |
| **All (inventory)** | **271** |
| Allow-listed (omitted below) | 98 |

_Generated at 2026-09-29T10:55:22+00:00_

<a id="public-268"></a>

## Public

Path anchor for children: `docs/i18n-inventory.md#public-268` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

_No hard-coded findings in this area (or all allow-listed)._

<a id="apes-cic-staff-269"></a>

## APES CIC staff

Path anchor for children: `docs/i18n-inventory.md#apes-cic-staff-269` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `resources/views/sub-cores/show.blade.php` | 20 | `blade.text` | What you can do here | `apes_cic.show.what_you_can_do_here` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 37 | `blade.text` | Open items in that may need a response or follow-up. | `apes_cic.show.open_items_in_that_may_need_a_response_o` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 42 | `blade.text` | What needs your attention | `apes_cic.show.what_needs_your_attention` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 54 | `blade.attribute.title` | $item->title | `apes_cic.show.item_title` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 65 | `blade.attribute.title` | You are all caught up. | `apes_cic.show.you_are_all_caught_up` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 81 | `blade.text` | Available plugins | `apes_cic.show.available_plugins` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 110 | `blade.attribute.title` | No plugins are currently available. | `apes_cic.show.no_plugins_are_currently_available` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 117 | `blade.text` | label, }; | `apes_cic.show.label` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 119 | `blade.text` | Quick links | `apes_cic.show.quick_links` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 138 | `blade.text` | View | `apes_cic.show.view` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 149 | `blade.text` | ['ticket', 'circle'], }; | `apes_cic.show.ticket_circle` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 159 | `blade.text` | Recent updates | `apes_cic.show.recent_updates` | `lang/en_GB/apes_cic.php` |

**Section total:** 12

<a id="admin-shell-270"></a>

## Admin shell

Path anchor for children: `docs/i18n-inventory.md#admin-shell-270` — extraction [#270](https://github.com/APESCIC/MyAPES-Account/issues/270).

_No hard-coded findings in this area (or all allow-listed)._

<a id="auth-emails-flash-validation-271"></a>

## Auth / emails / notifications / flash / validation

Path anchor for children: `docs/i18n-inventory.md#auth-emails-flash-validation-271` — extraction [#271](https://github.com/APESCIC/MyAPES-Account/issues/271).

_No hard-coded findings in this area (or all allow-listed)._

<a id="plugin-cases"></a>

## Plugin: Cases

Path anchor for children: `docs/i18n-inventory.md#plugin-cases` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/cases/resources/views/cic/index.blade.php` | 3 | `blade.text` | APES CIC | `cases::ui.index.a_p_e_s_c_i_c` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 3 | `blade.text` | Cases | `cases::ui.index.cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 9 | `blade.text` | Formal casework including data access, privacy requests, complaints and escalated enquiries. Use tickets for general support. | `cases::ui.index.formal_casework_including_data_access_pr` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 13 | `blade.text` | Your available cases | `cases::ui.index.your_available_cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 17 | `blade.attribute.title` | No cases are available to you yet. | `cases::ui.index.no_cases_are_available_to_you_yet` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 24 | `blade.text` | ID | `cases::ui.index.i_d` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 25 | `blade.text` | Title | `cases::ui.index.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 26 | `blade.text` | Category | `cases::ui.index.category` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 27 | `blade.text` | Status | `cases::ui.index.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 28 | `blade.text` | Priority | `cases::ui.index.priority` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 29 | `blade.text` | Owner | `cases::ui.index.owner` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 30 | `blade.text` | Assigned | `cases::ui.index.assigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 42 | `blade.text` | Subcategory | `cases::ui.index.subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 55 | `blade.text` | Open | `cases::ui.index.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 65 | `blade.text` | Open a case | `cases::ui.index.open_a_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 82 | `blade.text` | Select subcategory | `cases::ui.index.select_subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 95 | `blade.text` | Related website or system | `cases::ui.index.related_website_or_system` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 97 | `blade.text` | Select website | `cases::ui.index.select_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 107 | `blade.text` | Details | `cases::ui.index.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 110 | `blade.text` | Evidence screenshots (optional) | `cases::ui.index.evidence_screenshots_optional` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 112 | `blade.text` | Evidence screencast (optional) | `cases::ui.index.evidence_screencast_optional` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 115 | `blade.text` | Open case | `cases::ui.index.open_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 3 | `blade.text` | APES CIC | `cases::ui.show.a_p_e_s_c_i_c` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 6 | `blade.text` | Case # - | `cases::ui.show.case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 13 | `blade.text` | Owner | `cases::ui.show.owner` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 17 | `blade.text` | Assigned staff | `cases::ui.show.assigned_staff` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 20 | `blade.text` | Unassigned | `cases::ui.show.unassigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 27 | `blade.text` | Category | `cases::ui.show.category` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 32 | `blade.text` | Subcategory | `cases::ui.show.subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 38 | `blade.text` | Related website | `cases::ui.show.related_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 43 | `blade.text` | Status | `cases::ui.show.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 47 | `blade.text` | Priority | `cases::ui.show.priority` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 71 | `blade.text` | Select subcategory | `cases::ui.show.select_subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 95 | `blade.text` | Related website or system | `cases::ui.show.related_website_or_system` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 97 | `blade.text` | Select website | `cases::ui.show.select_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 129 | `blade.text` | Save case | `cases::ui.show.save_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 134 | `blade.text` | Attachments | `cases::ui.show.attachments` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 136 | `blade.text` | Add update | `cases::ui.show.add_update` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 137 | `blade.text` | ( KB) | `cases::ui.show.k_b` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 138 | `blade.text` | Visibility | `cases::ui.show.visibility` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 141 | `blade.text` | Public | `cases::ui.show.public` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 142 | `blade.text` | Internal staff only | `cases::ui.show.internal_staff_only` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 146 | `blade.text` | To add evidence files, use Save case after uploading on a separate update path, or attach when opening the case. | `cases::ui.show.to_add_evidence_files_use_save_case_afte` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 154 | `blade.text` | Add evidence screenshots | `cases::ui.show.add_evidence_screenshots` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 156 | `blade.text` | Add evidence screencast | `cases::ui.show.add_evidence_screencast` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 158 | `blade.text` | Upload evidence | `cases::ui.show.upload_evidence` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 162 | `blade.text` | Reopen this case before adding another update. | `cases::ui.show.reopen_this_case_before_adding_another_u` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 169 | `blade.text` | Delete case | `cases::ui.show.delete_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 172 | `blade.text` | Back | `cases::ui.show.back` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 184 | `blade.text` | Open | `cases::ui.show.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 192 | `blade.text` | Activity | `cases::ui.show.activity` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 201 | `blade.text` | No updates have been added. | `cases::ui.show.no_updates_have_been_added` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 3 | `blade.text` | Cases | `cases::ui.index.cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 7 | `blade.text` | APES Shelter and Rescue | `cases::ui.index.a_p_e_s_shelter_and_rescue` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 8 | `blade.text` | Case management | `cases::ui.index.case_management` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 9 | `blade.text` | Track adoption, surrender, rescue and fostering workflows. | `cases::ui.index.track_adoption_surrender_rescue_and_fost` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | ID | `cases::ui.index.i_d` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Pet | `cases::ui.index.pet` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Status | `cases::ui.index.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Title | `cases::ui.index.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Type | `cases::ui.index.type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 23 | `blade.text` | Open | `cases::ui.index.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 32 | `blade.text` | Create case | `cases::ui.index.create_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 41 | `blade.text` | Case type | `cases::ui.index.case_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 47 | `blade.text` | Details | `cases::ui.index.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | APES Shelter and Rescue | `cases::ui.show.a_p_e_s_shelter_and_rescue` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | Case # - | `cases::ui.show.case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | Pet: \| Type: | `cases::ui.show.pet_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 10 | `blade.text` | Status | `cases::ui.show.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 13 | `blade.text` | Details | `cases::ui.show.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 24 | `blade.text` | Case type | `cases::ui.show.case_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 32 | `blade.text` | Title | `cases::ui.show.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 58 | `blade.text` | Update case | `cases::ui.show.update_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 66 | `blade.text` | Assigned staff | `cases::ui.show.assigned_staff` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 68 | `blade.text` | Unassigned | `cases::ui.show.unassigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 74 | `blade.text` | Update assignment | `cases::ui.show.update_assignment` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 78 | `blade.text` | Back | `cases::ui.show.back` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 84 | `blade.text` | Add update | `cases::ui.show.add_update` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 86 | `blade.text` | Visibility | `cases::ui.show.visibility` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 89 | `blade.text` | Public | `cases::ui.show.public` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 90 | `blade.text` | Internal staff only | `cases::ui.show.internal_staff_only` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 96 | `blade.text` | Reopen this case before adding another update. | `cases::ui.show.reopen_this_case_before_adding_another_u` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 100 | `blade.text` | Activity | `cases::ui.show.activity` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 109 | `blade.text` | No updates have been added. | `cases::ui.show.no_updates_have_been_added` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 127 | `php.validation_exception` | Choose a valid subcategory for the selected category. | `cases::ui.case_category_resolver.choose_a_valid_subcategory_for_the_selec` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 137 | `php.validation_exception` | Select which website or system is involved. | `cases::ui.case_category_resolver.select_which_website_or_system_is_involv` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 142 | `php.validation_exception` | Select a valid website. | `cases::ui.case_category_resolver.select_a_valid_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 147 | `php.validation_exception` | Select a valid website. | `cases::ui.case_category_resolver.select_a_valid_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 150 | `php.validation_exception` | Attachments are not available for this subcategory. | `cases::ui.case_controller.attachments_are_not_available_for_this_s` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 266 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 383 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 391 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 409 | `php.flash` | Case updated. | `cases::flash.case_controller.case_updated` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 432 | `php.flash` | Case deleted. | `cases::flash.case_controller.case_deleted` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 483 | `php.validation_exception` | Missing module context for cases. | `cases::ui.case_controller.missing_module_context_for_cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 615 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 692 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 705 | `php.flash` | Case updated. | `cases::flash.case_controller.case_updated` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseUpdateController.php` | 60 | `php.validation_exception` | Reopen the case before adding another update. | `cases::ui.case_update_controller.reopen_the_case_before_adding_another_up` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseUpdateController.php` | 126 | `php.flash` | Case update added. | `cases::flash.case_update_controller.case_update_added` | `plugins/cases/lang/en_GB/flash.php` |

**Section total:** 100

<a id="plugin-tickets"></a>

## Plugin: Tickets

Path anchor for children: `docs/i18n-inventory.md#plugin-tickets` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 3 | `blade.text` | Tickets | `tickets::ui.index.tickets` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 17 | `blade.attribute.title` | No tickets are available to you yet. | `tickets::ui.index.no_tickets_are_available_to_you_yet` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 24 | `blade.text` | ID | `tickets::ui.index.i_d` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 25 | `blade.text` | Subject | `tickets::ui.index.subject` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 26 | `blade.text` | Area | `tickets::ui.index.area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 27 | `blade.text` | Status | `tickets::ui.index.status` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 28 | `blade.text` | Priority | `tickets::ui.index.priority` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 29 | `blade.text` | Owner | `tickets::ui.index.owner` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 30 | `blade.text` | Assigned | `tickets::ui.index.assigned` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 43 | `blade.text` | Subcategory | `tickets::ui.index.subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 59 | `blade.text` | Open | `tickets::ui.index.open` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 69 | `blade.text` | Create ticket | `tickets::ui.index.create_ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 79 | `blade.text` | Service area | `tickets::ui.index.service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 82 | `blade.text` | Select service area | `tickets::ui.index.select_service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 100 | `blade.text` | Select subcategory | `tickets::ui.index.select_subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 116 | `blade.text` | Affected website | `tickets::ui.index.affected_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 117 | `blade.text` | (required) | `tickets::ui.index.required` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 120 | `blade.text` | Select website | `tickets::ui.index.select_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 131 | `blade.text` | Description | `tickets::ui.index.description` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 135 | `blade.text` | Screenshots (optional) | `tickets::ui.index.screenshots_optional` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 137 | `blade.text` | Screencast (optional) | `tickets::ui.index.screencast_optional` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 5 | `blade.text` | Ticket # - | `tickets::ui.show.ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 13 | `blade.text` | Owner | `tickets::ui.show.owner` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 17 | `blade.text` | Assigned staff | `tickets::ui.show.assigned_staff` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 20 | `blade.text` | Unassigned | `tickets::ui.show.unassigned` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 27 | `blade.text` | Service area | `tickets::ui.show.service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 38 | `blade.text` | Subcategory | `tickets::ui.show.subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 44 | `blade.text` | Affected website | `tickets::ui.show.affected_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 49 | `blade.text` | Status | `tickets::ui.show.status` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 53 | `blade.text` | Priority | `tickets::ui.show.priority` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 59 | `blade.text` | Attachments | `tickets::ui.show.attachments` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 84 | `blade.text` | Add message | `tickets::ui.show.add_message` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 86 | `blade.text` | Visibility | `tickets::ui.show.visibility` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 89 | `blade.text` | Public | `tickets::ui.show.public` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 90 | `blade.text` | Internal staff only | `tickets::ui.show.internal_staff_only` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 95 | `blade.text` | Add screenshots | `tickets::ui.show.add_screenshots` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 97 | `blade.text` | Add screencast | `tickets::ui.show.add_screencast` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 103 | `blade.text` | ( KB) | `tickets::ui.show.k_b` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 130 | `blade.text` | Update ownership | `tickets::ui.show.update_ownership` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 134 | `blade.text` | Back | `tickets::ui.show.back` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 140 | `blade.text` | Delete ticket | `tickets::ui.show.delete_ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 154 | `blade.text` | Open | `tickets::ui.show.open` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 162 | `blade.text` | Activity | `tickets::ui.show.activity` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 164 | `blade.text` | You can add an update (comment) to this ticket. | `tickets::ui.show.you_can_add_an_update_comment_to_this_ti` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 143 | `php.validation_exception` | Attachments are not available for this subcategory. | `tickets::ui.ticket_controller.attachments_are_not_available_for_this_s` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 191 | `php.flash` | Your ticket has been saved. | `tickets::flash.ticket_controller.your_ticket_has_been_saved` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 423 | `php.validation_exception` | Select a ticket change, add a message, or attach a file before submitting. | `tickets::ui.ticket_controller.select_a_ticket_change_add_a_message_or` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 464 | `php.flash` | Your update has been saved. | `tickets::flash.ticket_controller.your_update_has_been_saved` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 487 | `php.flash` | Ticket deleted. | `tickets::flash.ticket_controller.ticket_deleted` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 127 | `php.validation_exception` | Choose a valid subcategory for the selected service area. | `tickets::ui.ticket_category_resolver.choose_a_valid_subcategory_for_the_selec` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 137 | `php.validation_exception` | Select which website is affected. | `tickets::ui.ticket_category_resolver.select_which_website_is_affected` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 142 | `php.validation_exception` | Select a valid website. | `tickets::ui.ticket_category_resolver.select_a_valid_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 147 | `php.validation_exception` | Select a valid website. | `tickets::ui.ticket_category_resolver.select_a_valid_website` | `plugins/tickets/lang/en_GB/ui.php` |

**Section total:** 53

<a id="plugin-recruitment"></a>

## Plugin: Recruitment

Path anchor for children: `docs/i18n-inventory.md#plugin-recruitment` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/recruitment/resources/views/public/index.blade.php` | 15 | `blade.text` | roles All open roles | `recruitment::public.index.roles_all_open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 9 | `blade.text` | Location: · Commitment: | `recruitment::public.show.location_commitment` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 23 | `blade.text` | You already applied for this role. Status: | `recruitment::public.show.you_already_applied_for_this_role_status` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 1 | `blade.attribute.aria-label` | Recruitment manage sections | `recruitment::staff._navigation.recruitment_manage_sections` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 6 | `blade.text` | Roles | `recruitment::staff._navigation.roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 12 | `blade.text` | Applications | `recruitment::staff._navigation.applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 3 | `blade.text` | Applications | `recruitment::staff.index.applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 11 | `blade.text` | Review applications for APES CIC recruitment roles. | `recruitment::staff.index.review_applications_for_a_p_e_s_c_i_c_recruit` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 15 | `blade.text` | Filter | `recruitment::staff.index.filter` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 15 | `blade.attribute.aria-label` | Filter applications | `recruitment::staff.index.filter_applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 19 | `blade.text` | Status | `recruitment::staff.index.status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 21 | `blade.text` | All statuses | `recruitment::staff.index.all_statuses` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 30 | `blade.text` | Category | `recruitment::staff.index.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 32 | `blade.text` | All categories | `recruitment::staff.index.all_categories` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 41 | `blade.text` | Role | `recruitment::staff.index.role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 43 | `blade.text` | All roles | `recruitment::staff.index.all_roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 54 | `blade.text` | Clear | `recruitment::staff.index.clear` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 64 | `blade.attribute.title` | No applications match. | `recruitment::staff.index.no_applications_match` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 72 | `blade.text` | Applicant | `recruitment::staff.index.applicant` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 74 | `blade.text` | Submitted | `recruitment::staff.index.submitted` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 91 | `blade.text` | Open | `recruitment::staff.index.open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.show.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 14 | `blade.text` | Applicant | `recruitment::staff.show.applicant` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 23 | `blade.text` | Category | `recruitment::staff.show.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 27 | `blade.text` | Submitted | `recruitment::staff.show.submitted` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 31 | `blade.text` | Application ID | `recruitment::staff.show.application_i_d` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 36 | `blade.text` | Review started | `recruitment::staff.show.review_started` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 42 | `blade.text` | Decided | `recruitment::staff.show.decided` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 48 | `blade.text` | Withdrawn | `recruitment::staff.show.withdrawn` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 54 | `blade.text` | Applicant statement | `recruitment::staff.show.applicant_statement` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 60 | `blade.text` | Staff notes | `recruitment::staff.show.staff_notes` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 69 | `blade.text` | Update status | `recruitment::staff.show.update_status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 80 | `blade.text` | Save review | `recruitment::staff.show.save_review` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 85 | `blade.text` | Back to applications | `recruitment::staff.show.back_to_applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 87 | `blade.text` | View role | `recruitment::staff.show.view_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 3 | `blade.text` | Roles | `recruitment::staff.index.roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 8 | `blade.text` | APES CIC | `recruitment::staff.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 10 | `blade.text` | Staff, volunteering, and student openings for APES CIC. | `recruitment::staff.index.staff_volunteering_and_student_openings` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 18 | `blade.attribute.title` | No recruitment roles yet. | `recruitment::staff.index.no_recruitment_roles_yet` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 25 | `blade.text` | Title | `recruitment::staff.index.title` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 26 | `blade.text` | Category | `recruitment::staff.index.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 27 | `blade.text` | Status | `recruitment::staff.index.status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 38 | `blade.text` | Open | `recruitment::staff.index.open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 49 | `blade.text` | Create role | `recruitment::staff.index.create_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 70 | `blade.text` | Location | `recruitment::staff.index.location` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 74 | `blade.text` | Commitment | `recruitment::staff.index.commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 78 | `blade.text` | Summary | `recruitment::staff.index.summary` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 80 | `blade.text` | Description | `recruitment::staff.index.description` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 82 | `blade.text` | Save recruitment role | `recruitment::staff.index.save_recruitment_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.show.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 15 | `blade.text` | Created | `recruitment::staff.show.created` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 15 | `blade.text` | Created by | `recruitment::staff.show.created_by` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 29 | `blade.text` | ID | `recruitment::staff.show.i_d` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 29 | `blade.text` | Location: \| Commitment: | `recruitment::staff.show.location_commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 34 | `blade.text` | Published | `recruitment::staff.show.published` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 40 | `blade.text` | Closed | `recruitment::staff.show.closed` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 50 | `blade.text` | Location | `recruitment::staff.show.location` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 52 | `blade.text` | Commitment | `recruitment::staff.show.commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 63 | `blade.text` | Publish / open | `recruitment::staff.show.publish_open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 69 | `blade.text` | Close role | `recruitment::staff.show.close_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 80 | `blade.text` | Title | `recruitment::staff.show.title` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 84 | `blade.text` | Category | `recruitment::staff.show.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 104 | `blade.text` | Summary | `recruitment::staff.show.summary` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 106 | `blade.text` | Description | `recruitment::staff.show.description` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 109 | `blade.text` | Update recruitment role | `recruitment::staff.show.update_recruitment_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 110 | `blade.text` | Back | `recruitment::staff.show.back` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/src/Http/Controllers/PublicRecruitmentApplicationController.php` | 64 | `php.validation_exception` | You have already applied for this role. Each person may apply once. | `recruitment::ui.public_recruitment_application_controller.you_have_already_applied_for_this_role_e` | `plugins/recruitment/lang/en_GB/ui.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentApplicationController.php` | 129 | `php.validation_exception` | This application can no longer be reviewed. | `recruitment::ui.recruitment_application_controller.this_application_can_no_longer_be_review` | `plugins/recruitment/lang/en_GB/ui.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentApplicationController.php` | 152 | `php.flash` | Application review saved. | `recruitment::flash.recruitment_application_controller.application_review_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 52 | `php.flash` | Recruitment role saved. | `recruitment::flash.recruitment_role_controller.recruitment_role_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 88 | `php.flash` | Recruitment role saved. | `recruitment::flash.recruitment_role_controller.recruitment_role_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 111 | `php.flash` | Recruitment role is now open. | `recruitment::flash.recruitment_role_controller.recruitment_role_is_now_open` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 133 | `php.flash` | Recruitment role closed. | `recruitment::flash.recruitment_role_controller.recruitment_role_closed` | `plugins/recruitment/lang/en_GB/flash.php` |

**Section total:** 74

<a id="plugin-consultations"></a>

## Plugin: Consultations

Path anchor for children: `docs/i18n-inventory.md#plugin-consultations` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | APES Pet Care Clinic | `consultations::ui.index.a_p_e_s_pet_care_clinic` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | Consultations | `consultations::ui.index.consultations` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | Pet | `consultations::ui.index.pet` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 9 | `blade.text` | Consultation management | `consultations::ui.index.consultation_management` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | ID | `consultations::ui.index.i_d` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Scheduled | `consultations::ui.index.scheduled` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Status | `consultations::ui.index.status` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Subject | `consultations::ui.index.subject` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 23 | `blade.text` | Open | `consultations::ui.index.open` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 32 | `blade.text` | Create consultation | `consultations::ui.index.create_consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 41 | `blade.text` | (dd/mm/yyyy) | `consultations::ui.index.dd_mm_yyyy` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 41 | `blade.text` | Scheduled for | `consultations::ui.index.scheduled_for` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 42 | `blade.attribute.placeholder` | dd/mm/yyyy HH:mm:ss | `consultations::ui.index.dd_mm_yyyy_h_h_mm_ss` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 47 | `blade.text` | Notes | `consultations::ui.index.notes` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 3 | `blade.text` | APES Pet Care Clinic | `consultations::ui.show.a_p_e_s_pet_care_clinic` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 3 | `blade.text` | id) @section('content') @inject('ukDateTime', \App\Support\UkDateTime::class) | `consultations::ui.show.id_section_content_inject_uk_date_time_app` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 9 | `blade.text` | Consultation # - | `consultations::ui.show.consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 12 | `blade.text` | Status | `consultations::ui.show.status` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 14 | `blade.text` | Scheduled for | `consultations::ui.show.scheduled_for` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 16 | `blade.text` | Notes | `consultations::ui.show.notes` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 19 | `blade.text` | Assigned staff | `consultations::ui.show.assigned_staff` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 24 | `blade.text` | Current assignment is preserved but is no longer eligible. | `consultations::ui.show.current_assignment_is_preserved_but_is_n` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 51 | `blade.text` | (dd/mm/yyyy) | `consultations::ui.show.dd_mm_yyyy` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 52 | `blade.attribute.placeholder` | dd/mm/yyyy HH:mm:ss | `consultations::ui.show.dd_mm_yyyy_h_h_mm_ss` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 61 | `blade.text` | Update consultation | `consultations::ui.show.update_consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 69 | `blade.text` | Change assigned staff | `consultations::ui.show.change_assigned_staff` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 71 | `blade.text` | Choose an assignment change | `consultations::ui.show.choose_an_assignment_change` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 72 | `blade.text` | Clear assignment | `consultations::ui.show.clear_assignment` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 78 | `blade.text` | Update assignment | `consultations::ui.show.update_assignment` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 82 | `blade.text` | Back | `consultations::ui.show.back` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/src/Http/Controllers/ConsultationController.php` | 144 | `php.validation_exception` | No consultation changes were requested. | `consultations::ui.consultation_controller.no_consultation_changes_were_requested` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/src/Http/Controllers/ConsultationController.php` | 203 | `php.flash` | Consultation updated. | `consultations::flash.consultation_controller.consultation_updated` | `plugins/consultations/lang/en_GB/flash.php` |

**Section total:** 32

<a id="plugin-pet-profiles"></a>

## Plugin: Pet Profiles

Path anchor for children: `docs/i18n-inventory.md#plugin-pet-profiles` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

_No hard-coded findings in this area (or all allow-listed)._

<a id="other-unclassified"></a>

## Other / unclassified

Path anchor for children: `docs/i18n-inventory.md#other-unclassified`.

_No hard-coded findings in this area (or all allow-listed)._

## Allow-list / false positives

Configured in `config/lang_inventory.php`. Matches are omitted from the tables above so extraction PRs stay focused. CI (#276) should load the same config when warning on new hard-coded strings.

Common categories: HTTP method tokens, digit-only snippets, HTML entities, decorative glyphs, long path/FQCN-like tokens without spaces.

## Design notes for #276

1. Call `HardCodedStringScanner::scan()` (or `inventoryFindings()`).
2. Prefer report-only warnings on **changed files** until extraction waves finish.
3. Re-use `config/lang_inventory.php` allow-list — do not fork a second list.
4. Optional: diff against committed JSON snapshot when one is published for CI.
